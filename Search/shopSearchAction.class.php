<?php

/**
 * Description of searchShopAction
 *
 * @author Sanjeev
 */
class shopSearchAction extends cardbookBaseApiActions
{

   /**
   * リクエストメソッド POST
   */
  const REQUEST_METHOD_POST = 'post';

  /**
   * リクエストメソッド GET
   */
  const REQUEST_METHOD_GET = 'get';

  /**
   * リクエストメソッド PUT
   */
  const REQUEST_METHOD_PUT = 'put';

  /**
   * リクエストメソッド DELETE
   */
  const REQUEST_METHOD_DELETE = 'delete';

  const DISPLAY_LIMIT = 20;

  const DATABASE_NAME = 'epark';

  const SEARCH_TYPE_KEY    = "search_type";

  const SEARCH_TYPE_AREA   = "area";

  const SEARCH_TYPE_GPS    = "gps";

  const SEARCH_TYPE_FREE_WORD    = "freeword";

  const SEARCH_TYPE_GPS_N_FREE_WORD = "search";

  const GROUP_SHOP = null;


  protected $requiresAuth = true;

  /**
   *
   * @var string
   */
  protected $methodAllowed = self::REQUEST_METHOD_POST;

  /**
   *
   * @var array
   */
  protected $scopesRequired = array(xapiOAuth2Scope::BASIC);

  public function executeShopSearch(sfWebRequest $request)
  {
      $member = $this->getMe();
      $prefecture_id = $request->getParameter("prefecture_id");
      $latitude = $request->getParameter("latitude");
      $longitude = $request->getParameter("longitude");
      $keyword = $request->getParameter("keyword");
      $page_no = $request->getParameter("page");
      if($page_no == null || $page_no == ""){
        $page_no = 1;
      }
      $pager = null;
      $search_shop = null;
      $xlCategoryId = ShopCategoryPeer::XL_CATEGORY_ID_GOURMET;



      if($latitude == null || $latitude ==""){
          throw new cardbook400BadRequestException("latitude::Required\tlatitudeが未入力");
      }

      if(!is_numeric($latitude)){
          throw new cardbook400BadRequestException("latitude::Invalid\tlatitudeが不正");
      }

      if($longitude == null || $longitude ==""){
          throw new cardbook400BadRequestException("longitude::Required\tlongitudeが未入力");
      }

      if(!is_numeric($longitude)){
          throw new cardbook400BadRequestException("longitude::Invalid\tlongitudeが不正");
      }

      if(!preg_match('/^[1-9][0-9]*$/',$page_no)){
          throw new cardbook400BadRequestException("page::Invalid\tpageが不正");
      }

      //considered when prefecture_id is not set
      if(trim($latitude) != "" && trim($keyword) != "" && trim($prefecture_id) == ""){
        $search_shop = 1;
        $address = null;
      if ($request->hasParameter("latitude")) {
      $latitude = $request->getParameter("latitude", null);
      if (empty($latitude)) {
        $gps = epMobileGps::factory($this->getUser()->getAgent(), $request->getGetParameters());
        $latitude = $gps->getLatitude();
      }
    }
    if ($request->hasParameter("longitude")) {
      $longitude = $request->getParameter("longitude", null);
      if (empty($longitude)) {
        $gps = epMobileGps::factory($this->getUser()->getAgent(), $request->getGetParameters());
        $longitude = $gps->getLongitude();
      }
    }
    $shop_name = $request->getParameter("keyword");
    $searchType = self::SEARCH_TYPE_GPS_N_FREE_WORD;
    $pager = $this->getShopSearchCriteriaFromSpSearchConditionsForGpsKey($xlCategoryId, $searchType, $address, $latitude, $longitude, $shop_name, $member, $page_no, false);
   }

   //considered when prefecture_id is set
   if(trim($prefecture_id) != "" || $prefecture_id != null){
      $pref_id = $request->getParameter("prefecture_id");
      if(!preg_match('/^[1-9][0-9]*$/', $pref_id) || $pref_id < 1 || $pref_id > 47){
          throw new cardbook400BadRequestException("prefecture_id::Invalid\tprefecture_idが不正");
      }
      $prefecture = PrefecturePeer::retrieveByPK($pref_id);
      $prefName = $prefecture->getName();
      $searchType = self::SEARCH_TYPE_AREA;

        $address = $prefName;
        $latitude = null;
        $longitude = null;
        $shop_name = null;
        $pager = $this->getShopListPager($xlCategoryId, $searchType, $address, $latitude, $longitude, $shop_name, $member, $page_no, false);
      }
      //considered for GPS Search
      if((trim($latitude) != "" || trim($longitude) != "" || $latitude != null || $longitude != null) && !isset($search_shop)){
      $searchType = self::SEARCH_TYPE_GPS;
      if ($request->hasParameter("latitude")) {
      $latitude = $request->getParameter("latitude", null);
      if (empty($latitude)) {
        $gps = epMobileGps::factory($this->getUser()->getAgent(), $request->getGetParameters());
        $latitude = $gps->getLatitude();
      }
    }
    if ($request->hasParameter("longitude")) {
      $longitude = $request->getParameter("longitude", null);
      if (empty($longitude)) {
        $gps = epMobileGps::factory($this->getUser()->getAgent(), $request->getGetParameters());
        $longitude = $gps->getLongitude();
      }
    }
    $pager = $this->getShopListPager($xlCategoryId, $searchType, null, $latitude, $longitude, null, $member, $page_no, false);
    }
    //for search through keyword
    if (($keyword != "" || $keyword != null) && !isset($search_shop)) {
      $searchType = self::SEARCH_TYPE_FREE_WORD;
      $shop_name = $request->getParameter("keyword");
      $pager = $this->getShopListPager($xlCategoryId, $searchType, null, null, null, $shop_name, $member, $page_no, false);
    }
    if(isset($pager)){
        $search_detail = array(
        'total page'       => $pager->getLastPage(),
        'current_page'     => ($pager->getLastPage()) ? $pager->getPage() : 0,
        'shop_num'         => count($pager->getResults()),
        'shop_list'        => array(
        ),
      );
        foreach($pager->getResults() as $result){

        $shop_id = $result->getShopCode();
        $shop = ShopPeer::retrieveByPK($shop_id);
        $imageName = $shop->getImageName();
        if (empty($imageName)) {
          $shopPhoto = null;
        } else {
          $shopPhoto = '/uploads/shop_images/' . $shop->getImageName();
        }

        $ifMembershipShop = epMemberFacade::isMembershipShop($shop_id);
        if (!$ifMembershipShop) {
             continue;
        }
        $shop_id2 = $shop_id;
        $group_id = $this->getGroupId($shop_id);
        if ($group_id != 0) {
          $shop_id2 = 0;
        }
        $res = TCardbookShopPeer::isPos($shop_id2, $group_id);
        if($res){
          $is_pos = 0;
        }else{
          $is_pos = 1;
        }
        if (!empty($shopPhoto)) {
          $image = $request->getUriPrefix2() . $shopPhoto;
        } else {
          $image = null;
        }
        $lat1 = $request->getParameter("latitude");
        $lon1 = $request->getParameter("longitude");
        $lat2 = $result->getLatitude();
        $lon2 = $result->getLongitude();
        $distance = $this->calDistance($lat1,$lon1,$lat2,$lon2);
            $shop_lists = array(
                'group_id'   => $this->getGroupId($shop_id),
                'shop_id'    => $shop_id,
                'name'       => $result->getName(),
                'image'      => $image,
                'tel'        => $result->getTel(),
                'address'    => $result->getPrefAddress() . $result->getAddress2(),
                'prefecture' => PrefecturePeer::getPrefectureName($result->getPrefId()),
                'address1'   => $result->getAddress1(),
                'address2'   => $result->getAddress2(),
                'latitude'   => $result->getLatitude(),
                'longitude'  => $result->getLongitude(),
                'category'   => $result->getCategoryName(),
                'distance'   => $distance,
                'is_pos'     => $is_pos,
            );

            array_push($search_detail['shop_list'], $shop_lists);
        }
    }
    return $this->renderText(json_encode($search_detail));
  }

  //returns pager as per set condition
  public static function getShopListPager($xlCategoryId, $searchType, $address, $latitude, $longitude, $shop_name, $member, $page, $onlyPublicationFlag = NULL)
  {
    $criteria = self::getShopSearchCriteriaFromSpSearchConditionsForFaspa($xlCategoryId, $searchType, $address, $latitude, $longitude, $shop_name, $member, $onlyPublicationFlag);
    return epPropelPagerFactory::createPropelPager('ShopSearch', self::DISPLAY_LIMIT, $criteria, $page, "doSearch", "doSearchCount");
  }

  //returns pager when prefecture id is not set (considering the location value(gps) and keyword)
  public static function getShopSearchCriteriaFromSpSearchConditionsForGpsKey($xlCategoryId, $searchType, $address, $latitude, $longitude, $shop_name, $member, $page, $onlyPublicationFlag = NULL)
  {
    $criteria = new Criteria();
    $criteria->clearSelectColumns();
    $criteria->addAlias("spm", ShopPointMstPeer::TABLE_NAME);
    $criteria->addJoin(ShopSearchPeer::SHOP_CODE, ShopPointMstPeer::alias("spm", ShopPointMstPeer::SHOP_ID), Criteria::LEFT_JOIN);
    $criteria->addJoin(ShopSearchPeer::SHOP_CODE, ShopAuthConfigPeer::SHOP_ID, Criteria::LEFT_JOIN);
    $criteria->addJoin(ShopAuthConfigPeer::SHOP_ID, ShopAuthPeer::SHOP_ID, Criteria::LEFT_JOIN);
    $criteria->addJoin( ShopSearchPeer::SHOP_CODE, ShopPointRelationPeer::SHOP_ID, Criteria::LEFT_JOIN);
    $criteria->addAlias("spm2", ShopPointMstPeer::TABLE_NAME);
    $criteria->addJoin(ShopPointRelationPeer::SHOP_POINT_MST_ID, ShopPointMstPeer::alias("spm2", ShopPointMstPeer::ID), Criteria::LEFT_JOIN);

    $criteria->addJoin(ShopSearchPeer::SHOP_CATEGORY_ID, ShopCategoryPeer::ID, Criteria::LEFT_JOIN);
    $c1 = $criteria->getNewCriterion("spm.id", NULL, Criteria::ISNOTNULL);
    $c2 = $criteria->getNewCriterion("spm2.id", NULL, Criteria::ISNOTNULL);
    $c1->addOr($c2);
    $criteria->add($c1);
    $criteria->add("spm2.use_cardbook_flg", ShopPointMstPeer::USE_CARDBOOK_FLG_ENABLED, Criteria::EQUAL);
    self::addSelectAndGroupByColumnsForSearch($criteria);
    $criteria->addSelectColumn("GROUP_CONCAT(DISTINCT ".ShopCategoryPeer::NAME." SEPARATOR ' ') AS shop_category_names");
    $criteria->add(ShopSearchPeer::XL_CATEGORY_ID, $xlCategoryId, Criteria::EQUAL);
    $criteria->add(ShopSearchPeer::TIMESAVING_FLG,  ShopSearchPeer::TIMESAVING_FLG_TRUE, Criteria::EQUAL);
    $criteria->add(ShopSearchPeer::SERVICE_CODE, poServiceFacade::SERVICE_CODE_TM, Criteria::NOT_EQUAL);

    $criteria->add(ShopAuthPeer::STATUS, ShopAuthPeer::STATUS_ENABLED);
    $criteria->add(ShopAuthConfigPeer::AUTH_TYPE, ShopAuthConfig::AUTH_TYPE_TEST);
    $criteria->add(ShopAuthConfigPeer::AUTH_STATUS, ShopAuthConfig::AUTH_STATUS_ENABLED);

    if (isset($onlyPublicationFlag) && $onlyPublicationFlag == true) {
      $criteria->add(ShopSearchPeer::ONLY_PUBLICATION, ShopSearchPeer::ONLY_PUBLICATION_TRUE);
    } else {
      $criteria->add(ShopSearchPeer::ONLY_PUBLICATION, ShopSearchPeer::ONLY_PUBLICATION_FALSE);
    }
      $con = Propel::getConnection(ShopSearchPeer::DATABASE_NAME, Propel::CONNECTION_READ);
      $rangeK = ShopSearchPeer::GPS_RANGE_1;
      $rangeM = $rangeK * 2000;

      $distanceDegreeLat = poGeodeticUtil::convertDistanceToDegree($rangeM, 'lat');
      $distanceDegreeLng = poGeodeticUtil::convertDistanceToDegree($rangeM, 'lng');

      $custom = "MBRContains(
                  GeomFromText(CONCAT(
                    'LineString('
                        , CAST(%s-%s AS CHAR), ' ', CAST(%s-%s AS CHAR)
                        , ','
                        , CAST(%s+%s AS CHAR), ' ', CAST(%s+%s AS CHAR)
                        , ')'
                  ))
                  , GeomFromText(CONCAT('POINT(', CAST(shop_search.longitude AS CHAR), ' ', CAST(shop_search.latitude AS CHAR), ')'))
                )
      ";

      $custom = sprintf(
        $custom,
        $con->quote($longitude), $distanceDegreeLng, $con->quote($latitude), $distanceDegreeLat,
        $con->quote($longitude), $distanceDegreeLng, $con->quote($latitude), $distanceDegreeLat
      );

      $customOrder = sprintf(
        "GLength(GeomFromText(CONCAT('LineString(', %s, ' ', %s, ',',  CAST(shop_search.longitude AS CHAR), ' ', CAST(shop_search.latitude AS CHAR),')')))",
        $con->quote($longitude),
        $con->quote($latitude)
      );

      $criteria->add(ShopSearchPeer::LATITUDE, $custom, Criteria::CUSTOM);
      self::addFullKeywordSearchCriteria($criteria, $shop_name);
      $criteria->addAscendingOrderByColumn($customOrder);
      self::addJoinShopDisplayOrder($criteria);
      $criteria->addDescendingOrderByColumn(ShopDisplayOrderPeer::DISPLAY_POINT);
      return epPropelPagerFactory::createPropelPager('ShopSearch', self::DISPLAY_LIMIT, $criteria, $page, "doSearch", "doSearchCount");
   }

  //considerd for search through prefecture value and keyword
  public static function getShopSearchCriteriaFromSpSearchConditionsForFaspa($xlCategoryId, $searchType, $address, $latitude, $longitude, $shop_name, $member, $onlyPublicationFlag = NULL)
  {
    $criteria = new Criteria();
    $criteria->clearSelectColumns();
    $criteria->addAlias("spm", ShopPointMstPeer::TABLE_NAME);
    $criteria->addJoin(ShopSearchPeer::SHOP_CODE, ShopPointMstPeer::alias("spm", ShopPointMstPeer::SHOP_ID), Criteria::LEFT_JOIN);
    $criteria->addJoin(ShopSearchPeer::SHOP_CODE, ShopAuthConfigPeer::SHOP_ID, Criteria::LEFT_JOIN);
    $criteria->addJoin(ShopAuthConfigPeer::SHOP_ID, ShopAuthPeer::SHOP_ID, Criteria::LEFT_JOIN);
    $criteria->addJoin( ShopSearchPeer::SHOP_CODE, ShopPointRelationPeer::SHOP_ID, Criteria::LEFT_JOIN);
    $criteria->addAlias("spm2", ShopPointMstPeer::TABLE_NAME);
    $criteria->addJoin(ShopPointRelationPeer::SHOP_POINT_MST_ID, ShopPointMstPeer::alias("spm2", ShopPointMstPeer::ID), Criteria::LEFT_JOIN);
    $criteria->addJoin(ShopSearchPeer::SHOP_CATEGORY_ID, ShopCategoryPeer::ID, Criteria::LEFT_JOIN);
    $c1 = $criteria->getNewCriterion("spm.id", NULL, Criteria::ISNOTNULL);
    $c2 = $criteria->getNewCriterion("spm2.id", NULL, Criteria::ISNOTNULL);
    $c1->addOr($c2);
    $criteria->add($c1);
    $criteria->add("spm2.use_cardbook_flg", ShopPointMstPeer::USE_CARDBOOK_FLG_ENABLED, Criteria::EQUAL);
    self::addSelectAndGroupByColumnsForSearch($criteria);
    $criteria->addSelectColumn("GROUP_CONCAT(DISTINCT ".ShopCategoryPeer::NAME." SEPARATOR ' ') AS shop_category_names");
    $criteria->add(ShopSearchPeer::XL_CATEGORY_ID, $xlCategoryId, Criteria::EQUAL);
    $criteria->add(ShopSearchPeer::TIMESAVING_FLG, ShopSearchPeer::TIMESAVING_FLG_TRUE, Criteria::EQUAL);
    $criteria->add(ShopSearchPeer::SERVICE_CODE, poServiceFacade::SERVICE_CODE_TM, Criteria::NOT_EQUAL);

    $criteria->add(ShopAuthPeer::STATUS, ShopAuthPeer::STATUS_ENABLED);
    $criteria->add(ShopAuthConfigPeer::AUTH_TYPE, ShopAuthConfig::AUTH_TYPE_TEST);
    $criteria->add(ShopAuthConfigPeer::AUTH_STATUS, ShopAuthConfig::AUTH_STATUS_ENABLED);

    if (isset($onlyPublicationFlag) && $onlyPublicationFlag == true) {
      $criteria->add(ShopSearchPeer::ONLY_PUBLICATION, ShopSearchPeer::ONLY_PUBLICATION_TRUE);
    } else {
      $criteria->add(ShopSearchPeer::ONLY_PUBLICATION, ShopSearchPeer::ONLY_PUBLICATION_FALSE);
    }
    if (isset($latitude) && isset($longitude)) {
      $con = Propel::getConnection(ShopSearchPeer::DATABASE_NAME, Propel::CONNECTION_READ);
      $rangeK = ShopSearchPeer::GPS_RANGE_1;
      $rangeM = $rangeK * 2000;

      $distanceDegreeLat = poGeodeticUtil::convertDistanceToDegree($rangeM, 'lat');
      $distanceDegreeLng = poGeodeticUtil::convertDistanceToDegree($rangeM, 'lng');



      $custom = "MBRContains(
                  GeomFromText(CONCAT(
                    'LineString('
                        , CAST(%s-%s AS CHAR), ' ', CAST(%s-%s AS CHAR)
                        , ','
                        , CAST(%s+%s AS CHAR), ' ', CAST(%s+%s AS CHAR)
                        , ')'
                  ))
                  , GeomFromText(CONCAT('POINT(', CAST(shop_search.longitude AS CHAR), ' ', CAST(shop_search.latitude AS CHAR), ')'))
                )
      ";

      $custom = sprintf(
        $custom,
        $con->quote($longitude), $distanceDegreeLng, $con->quote($latitude), $distanceDegreeLat,
        $con->quote($longitude), $distanceDegreeLng, $con->quote($latitude), $distanceDegreeLat
      );

      $customOrder = sprintf(
        "GLength(GeomFromText(CONCAT('LineString(', %s, ' ', %s, ',',  CAST(shop_search.longitude AS CHAR), ' ', CAST(shop_search.latitude AS CHAR),')')))",
        $con->quote($longitude),
        $con->quote($latitude)
      );

      $criteria->add(ShopSearchPeer::LATITUDE, $custom, Criteria::CUSTOM);
    }

    if (isset($address) && mb_strlen($address) > 0) {
      $criteria->add(ShopSearchPeer::PREF_ADDRESS, "CONCAT(".ShopSearchPeer::PREF_ADDRESS.",".ShopSearchPeer::ADDRESS2.") like '%".$address."%'", Criteria::CUSTOM);
    }

    if (isset($shop_name) && mb_strlen($shop_name) > 0) {

      self::addFullKeywordSearchCriteria($criteria, $shop_name);
    }

    if (isset($latitude) && isset($longitude)) {
      $criteria->addAscendingOrderByColumn($customOrder);
      self::addJoinShopDisplayOrder($criteria);
      $criteria->addDescendingOrderByColumn(ShopDisplayOrderPeer::DISPLAY_POINT);
    } else {
      self::addJoinShopDisplayOrder($criteria);
      $criteria->addDescendingOrderByColumn(ShopDisplayOrderPeer::DISPLAY_POINT);
      $criteria->addDescendingOrderByColumn("CAST(".ShopSearchPeer::SHOP_CODE." AS SIGNED)");
    }

    if (ShopPeer::getKurasushiSpecialModeFlag($member)) {
      $criteria = ShopSearchPeer::addKurasushiGroupSpecialConditionsToSearchCriteria($criteria);
    }
    return $criteria;
  }

  //selecting required columns
   public static function addSelectAndGroupByColumnsForSearch(Criteria $criteria)
   {
    $criteria->addSelectColumn( ShopSearchPeer::SERVICE_CODE);
    $criteria->addSelectColumn(ShopSearchPeer::SHOP_CODE);
    $criteria->addSelectColumn(ShopSearchPeer::NAME);
    $criteria->addSelectColumn(ShopSearchPeer::NAME_KANA);
    $criteria->addSelectColumn("0 AS shop_category_id");
    $criteria->addSelectColumn("GROUP_CONCAT(DISTINCT ".ShopSearchPeer::CATEGORY_NAME." SEPARATOR ' ') AS category_name");
    $criteria->addSelectColumn("0 AS xl_category_id");
    $criteria->addSelectColumn("0 AS big_category_id");
    $criteria->addSelectColumn("0 AS mid_category_id");
    $criteria->addSelectColumn(ShopSearchPeer::ZIP_CODE);
    $criteria->addSelectColumn(ShopSearchPeer::PREF_ID);
    $criteria->addSelectColumn(ShopSearchPeer::PREF_NAME);
    $criteria->addSelectColumn(ShopSearchPeer::CITY_ID);
    $criteria->addSelectColumn(ShopSearchPeer::PREF_ADDRESS);
    $criteria->addSelectColumn(ShopSearchPeer::ADDRESS1);
    $criteria->addSelectColumn(ShopSearchPeer::ADDRESS2);
    $criteria->addSelectColumn(ShopSearchPeer::TEL);
    $criteria->addSelectColumn(ShopSearchPeer::FAX);
    $criteria->addSelectColumn(ShopSearchPeer::LATITUDE);
    $criteria->addSelectColumn(ShopSearchPeer::LONGITUDE);
    $criteria->addSelectColumn(ShopSearchPeer::KEYWORDS);
    $criteria->addSelectColumn(ShopSearchPeer::OWNER_SHOP_ID);
    $criteria->addSelectColumn(ShopSearchPeer::CUSTOM_SPECIFICATION_TYPE);
    $criteria->addSelectColumn(ShopSearchPeer::DEPARTMENT_KBN);
    $criteria->addSelectColumn(ShopSearchPeer::FUTURE_RESERVATION_ONLY);
    $criteria->addSelectColumn(ShopSearchPeer::MOBILE_WAIT_VIEW_STATUS);
    $criteria->addSelectColumn(ShopSearchPeer::MOBILE_WAIT_DISPLAY);
    $criteria->addSelectColumn(ShopSearchPeer::UNIT_PRICE_LUNCH);
    $criteria->addSelectColumn(ShopSearchPeer::UNIT_PRICE_DINNER);
    $criteria->addSelectColumn(ShopSearchPeer::TAKEOUT);
    $criteria->addSelectColumn(ShopSearchPeer::FREE_DRINK);
    $criteria->addSelectColumn(ShopSearchPeer::FREE_FOOD);
    $criteria->addSelectColumn(ShopSearchPeer::CREDIT_CARD);
    $criteria->addSelectColumn(ShopSearchPeer::PARKING);
    $criteria->addSelectColumn(ShopSearchPeer::CHILD_SEATS);
    $criteria->addSelectColumn(ShopSearchPeer::ZASHIKI);
    $criteria->addSelectColumn(ShopSearchPeer::SMOKING);
    $criteria->addSelectColumn(ShopSearchPeer::BARRIER_FREE);
    $criteria->addSelectColumn(ShopSearchPeer::LUNCH);
    $criteria->addSelectColumn(ShopSearchPeer::LATE_NIGHT);
    $criteria->addSelectColumn(ShopSearchPeer::RECEIVABLE);
    $criteria->addSelectColumn(ShopSearchPeer::CREATED_AT);
    $criteria->addSelectColumn(ShopSearchPeer::UPDATED_AT);
    $criteria->addSelectColumn(ShopSearchPeer::ONLY_PUBLICATION);
    $criteria->addSelectColumn("GROUP_CONCAT(DISTINCT ".ShopSearchPeer::SHOP_CATEGORY_ID." SEPARATOR ',') AS shop_category_ids");

    // GROUP BY COLUMNS
    $criteria->addGroupByColumn(ShopSearchPeer::SERVICE_CODE);
    $criteria->addGroupByColumn(ShopSearchPeer::SHOP_CODE);
    $criteria->addGroupByColumn(ShopSearchPeer::NAME);
    $criteria->addGroupByColumn(ShopSearchPeer::NAME_KANA);
    $criteria->addGroupByColumn(ShopSearchPeer::ZIP_CODE);
    $criteria->addGroupByColumn(ShopSearchPeer::PREF_ID);
    $criteria->addGroupByColumn(ShopSearchPeer::PREF_NAME);
    $criteria->addGroupByColumn(ShopSearchPeer::CITY_ID);
    $criteria->addGroupByColumn(ShopSearchPeer::PREF_ADDRESS);
    $criteria->addGroupByColumn(ShopSearchPeer::ADDRESS1);
    $criteria->addGroupByColumn(ShopSearchPeer::ADDRESS2);
    $criteria->addGroupByColumn(ShopSearchPeer::TEL);
    $criteria->addGroupByColumn(ShopSearchPeer::FAX);
    $criteria->addGroupByColumn(ShopSearchPeer::LATITUDE);
    $criteria->addGroupByColumn(ShopSearchPeer::LONGITUDE);
    $criteria->addGroupByColumn(ShopSearchPeer::KEYWORDS);
    $criteria->addGroupByColumn(ShopSearchPeer::OWNER_SHOP_ID);
    $criteria->addGroupByColumn(ShopSearchPeer::CUSTOM_SPECIFICATION_TYPE);
    $criteria->addGroupByColumn(ShopSearchPeer::DEPARTMENT_KBN);
    $criteria->addGroupByColumn(ShopSearchPeer::FUTURE_RESERVATION_ONLY);
    $criteria->addGroupByColumn(ShopSearchPeer::MOBILE_WAIT_VIEW_STATUS);
    $criteria->addGroupByColumn(ShopSearchPeer::MOBILE_WAIT_DISPLAY);
    $criteria->addGroupByColumn(ShopSearchPeer::UNIT_PRICE_LUNCH);
    $criteria->addGroupByColumn(ShopSearchPeer::UNIT_PRICE_DINNER);
    $criteria->addGroupByColumn(ShopSearchPeer::TAKEOUT);
    $criteria->addGroupByColumn(ShopSearchPeer::FREE_DRINK);
    $criteria->addGroupByColumn(ShopSearchPeer::FREE_FOOD);
    $criteria->addGroupByColumn(ShopSearchPeer::CREDIT_CARD);
    $criteria->addGroupByColumn(ShopSearchPeer::PARKING);
    $criteria->addGroupByColumn(ShopSearchPeer::CHILD_SEATS);
    $criteria->addGroupByColumn(ShopSearchPeer::ZASHIKI);
    $criteria->addGroupByColumn(ShopSearchPeer::SMOKING);
    $criteria->addGroupByColumn(ShopSearchPeer::BARRIER_FREE);
    $criteria->addGroupByColumn(ShopSearchPeer::LUNCH);
    $criteria->addGroupByColumn(ShopSearchPeer::LATE_NIGHT);
    $criteria->addGroupByColumn(ShopSearchPeer::RECEIVABLE);
    $criteria->addGroupByColumn(ShopSearchPeer::CREATED_AT);
    $criteria->addGroupByColumn(ShopSearchPeer::UPDATED_AT);
  }

  //calculate distance between 2 coordinates in meters
  protected function calDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000){
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);
  $lonDelta = $lonTo - $lonFrom;
  $a = pow(cos($latTo) * sin($lonDelta), 2) + pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
  $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

  $angle = atan2(sqrt($a), $b);
  $distance = intval($angle * $earthRadius);
      return $distance;
  }

  protected function getGroupId($shopId){
  $c = new Criteria();
  $c->addJoin(ShopPointMstPeer::ID, ShopPointRelationPeer::SHOP_POINT_MST_ID, Criteria::LEFT_JOIN);
  $c->add(ShopPointRelationPeer::SHOP_ID, $shopId);
  $result = ShopPointMstPeer::doSelectOne($c);
  $group_id = $result->getGroupId();
  if(isset($group_id)){
    return ($group_id);
  }else{
    return 0;
  }
  }

  public static function addFullKeywordSearchCriteria($criteria, $keyword) {
    if ( ! $criteria) {
     $criteria = new Criteria();
    }

    $pdo = Propel::getConnection(self::DATABASE_NAME);
    $keyword = mb_ereg_replace("-","ー",$keyword);
    $keyword = mb_ereg_replace("　"," ",$keyword);
    $keyword = mb_convert_kana($keyword, "KV");
    $keyword_list = explode(" ", $keyword);
    foreach ($keyword_list as $keyword_one) {
      if (!empty($keyword_one)) {
        self::addOneKeywordSearchCriteria($criteria, $keyword_one, $pdo);
      }
    }
    return $criteria;
  }

   public static function addOneKeywordSearchCriteria($criteria, $keyword, $pdo=null) {
    if (!$pdo) {
     $pdo = Propel::getConnection(self::DATABASE_NAME);
    }

    $keyword = $pdo->quote("%".str_replace('%', '%%', $keyword)."%");
    $pdo = Propel::getConnection(self::DATABASE_NAME);
    $column_keywords_formatted        =  ShopSearchPeer::KEYWORDS . ' collate utf8_unicode_ci LIKE ' . $keyword;

    $criteria->addAnd( ShopSearchPeer::KEYWORDS, $column_keywords_formatted     , Criteria:: CUSTOM);

    return $criteria;
  }

  public static function addJoinShopDisplayOrder($criteria)
  {
    if ( ! $criteria) {
      $criteria = new Criteria();
    }

    $criteria->addJoin(
      array(
        'SEARVICE_CODE'=> ShopSearchPeer::SERVICE_CODE,
        'SHOP_CODE'=>  ShopSearchPeer::SHOP_CODE
      ),
      array(
        'SEARVICE_CODE'=> ShopDisplayOrderPeer::SERVICE_CODE,
        'SHOP_CODE'=> ShopDisplayOrderPeer::SHOP_CODE
      ),
       Criteria::LEFT_JOIN
    );

    return $criteria;
  }

  //overriding cardbookBaseApiActions method
  protected function validateInput()
  {
    $input = $this->createInput();
    if($input->isValid() === false){
      $errors = $input->getErrorSchema()->getErrors();
      list($key, $error) = each($errors);
    }
  }

  //overriding cardbookBaseApiActions method
  protected function createInput()
  {
    $actionName = sfContext::getInstance()->getActionName();
    $formClassName = sprintf('%sForm', $actionName);
    if(!class_exists($formClassName)){
      throw new cardbook500InternalServerErrorException(
        sprintf('failed to load form-validator for action"%s".', $actionName)
      );
    }
    sfForm::disableCSRFProtection();
    $form = new $formClassName();
    $form->disableCSRFProtection();
    $form->setValidator(
      'access_token',
      new cardbookItemValidatorAccessToken(array('required' => false))
    );
    $form->setValidator(
      'encode',
      new cardbookItemValidatorEncode(array('required' => false))
    );

    $form->bind(
      strtoupper($this->getRequest()->getMethod()) === strtoupper(self::REQUEST_METHOD_GET)
            ? $this->getRequest()->getGetParameters() : $this->getRequest()->getPostParameters()
    );
    return $form;
  }
}
