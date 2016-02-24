<?php

/**
 * Description of searchShopAction
 *
 * @author Sanjeev
 */
class shopSearch2Action extends cardbookBaseApiActions
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

  // 予算時間帯
  const BUDGET_TIME_LUNCH  = 1; // 昼
  const BUDGET_TIME_DINNER = 2; // 夜

  // 予算下限
  const BUDGET_MIN_NONE  = 0;     // 下限無し
  const BUDGET_MIN_1000  = 1000;  // 1000円
  const BUDGET_MIN_2000  = 2000;  // 2000円
  const BUDGET_MIN_3000  = 3000;  // 3000円
  const BUDGET_MIN_5000  = 5000;  // 5000円
  const BUDGET_MIN_10000 = 10000; // 10000円
  const BUDGET_MIN_20000 = 20000; // 20000円

  // 予算上限
  const BUDGET_MAX_1000  = 1000;  // 1000円
  const BUDGET_MAX_2000  = 2000;  // 2000円
  const BUDGET_MAX_3000  = 3000;  // 3000円
  const BUDGET_MAX_5000  = 5000;  // 5000円
  const BUDGET_MAX_10000 = 10000; // 10000円
  const BUDGET_MAX_20000 = 20000; // 20000円
  const BUDGET_MAX_NONE  = 99999; // 上限無し


  const SEARCH_TYPE_KEY    = "search_type";

  const SEARCH_TYPE_AREA   = "area";

  const SEARCH_TYPE_GPS    = "gps";

  const SEARCH_TYPE_FREE_WORD    = "freeword";

  const SEARCH_TYPE_GPS_N_FREE_WORD = "search";

  const SEARCH_TYPE_CITY   = "city";

  const SEARCH_TYPE_BUDGET_N_PREF    = "budget_pref";

  const SEARCH_TYPE_BUDGET_N_GPS    = "budget_gps";

  const SEARCH_TYPE_CATEGORY    = "category";

  const GROUP_SHOP = null;

  const FLAG_CHECKIN_TRUE = 1;
  const FLAG_CHECKIN_FALSE = 0;
  const CHECKIN_AVOID = false;
  const CHECKIN = true;

  protected $requiresAuth = false;

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

  public function executeShopSearch2(sfWebRequest $request)
  {
//      $member = $this->getMe();
      $prefecture_id = $request->getParameter("prefecture_id");
      $latitude = $request->getParameter("latitude");
      $longitude = $request->getParameter("longitude");
      $keyword = $request->getParameter("keyword");
      $page = $request->getParameter("page");
      $budget_time = $request->getParameter("budget_time");
      $bmin = $request->getParameter("budget_min");
      $bmax = $request->getParameter("budget_max");
      $shop_big_category_id = $request->getParameter("shop_big_category_id");
      $shop_mid_category_id = $request->getParameter("shop_mid_category_id");
      $city_id = $request->getParameter("city_id");
      $this->type = $request->getParameter("type");
      $this->access_token = $request->getParameter("access_token");

      //setting default value to page if parameter is empty or null
      if($page == null || trim($page) == ""){
        $page = 1;
      }
      //setting empty values as null
      if(trim($prefecture_id) == "")
          $prefecture_id = null;
      if(trim($latitude) == "")
          $latitude = null;
      if(trim($longitude) == "")
          $longitude = null;
      if(trim($keyword) == "")
          $keyword = null;
      if(trim($budget_time) == "")
          $budget_time = null;
      if(trim($bmin) == "")
          $bmin = null;
      if(trim($bmax) == "")
          $bmax = null;
      if(trim($shop_big_category_id) == "")
          $shop_big_category_id = null;
      if(trim($shop_mid_category_id) == "")
          $shop_mid_category_id = null;
      if(trim($city_id) == "")
          $city_id = null;

      $checkin = self::CHECKIN_AVOID;
      foreach (explode(',', trim($this->type)) as $tp) {
        if ($tp == 1) {
          $checkin = self::CHECKIN;
        }
      }

      $dummy_gps_flg = 0;
      if ($latitude == null && $longitude == null) {
         $latitude = 35.6895281;
         $longitude = 139.7006271;
         $dummy_gps_flg = 1;
      }

      if($prefecture_id == null && $latitude == null && $longitude == null && $keyword == null && $budget_time == null && $shop_big_category_id == null && $shop_mid_category_id == null && $city_id == null){
          throw new cardbook400BadRequestException("Parameter::Required	\t 検索パラメータが指定されていない");
      }
      $pager = null;
      $xlCategoryId = ShopCategoryPeer::XL_CATEGORY_ID_GOURMET;

      //prefecture_id validation check
      if($prefecture_id != null){
      if(!preg_match('/^[1-9][0-9]*$/', $prefecture_id) || $prefecture_id < 1 || $prefecture_id > 47){
          throw new cardbook400BadRequestException("prefecture_id::Invalid\tprefecture_idが不正");
      }
      }

      //latitude validation check
      if($latitude != null){
        if(!is_numeric($latitude)){
          throw new cardbook400BadRequestException("latitude::Invalid\tlatitudeが不正");
        }
      }

      //longitude validation check
      if($longitude != null){
      if(!is_numeric($longitude)){
          throw new cardbook400BadRequestException("longitude::Invalid\tlongitudeが不正");
      }
      }

      //page no validation check
      if(!preg_match('/^[1-9][0-9]*$/',$page)){
          throw new cardbook400BadRequestException("page::Invalid\tpageが不正");
      }

      //budget_time validation check
      if($budget_time != null){
      if(intval($budget_time) == 1 || intval($budget_time) == 2){

      }else{
          throw new cardbook400BadRequestException("budget_time::Invalid\tbudget_timeが不正");
      }
      }

      //budget_time_minimum validation check
      if($bmin != null){
      if(!is_numeric($bmin) || intval($bmin)<0){
          throw new cardbook400BadRequestException("budget_min::Invalid\tbudget_minが不正");
      }
      }

      //budget_time_maximum validation check
      if($bmax != null){
      if(!is_numeric($bmax) || intval($bmax)<0){
          throw new cardbook400BadRequestException("budget_max::Invalid\tbudget_maxが不正");
      }elseif($bmax == 0) {
          throw new cardbook400BadRequestException("budget_max::Invalid\tbudget_max=0が不正");
      }
      }

      //swapping bmin and bmax
      if($bmin > $bmax){
          $tmp = $bmin;
          $bmin = $bmax;
          $bmax = $tmp;
      }

      //city_id validation
      if($city_id != null){
      if(!is_numeric($city_id) || intval($city_id)<=0){
          throw new cardbook400BadRequestException("city_id::Invalid\tcity_idが不正");
      }
      }

      //big_category_id validation
      if($shop_big_category_id != null){
          if(!is_numeric($shop_big_category_id) || intval($shop_big_category_id)<0){
              throw new cardbook400BadRequestException("shop_big_category_id::Invalid\tshop_big_category_idが不正");
          }
      }

      //mid_category_id validation check
      if($shop_mid_category_id != null){
          if(!is_numeric($shop_mid_category_id) || intval($shop_mid_category_id)<0){
              throw new cardbook400BadRequestException("shop_mid_category_id::Invalid\tshop_mid_category_idが不正");
          }
      }

      if(intval($shop_big_category_id) == 0)
          $shop_big_category_id = null;

      if(intval($shop_mid_category_id) == 0)
          $shop_mid_category_id = null;

      //GPS search----> this is called when prefecture_id and city_id both are not set (search by keyword, budget also considered.)
      if($latitude != null && $prefecture_id == null && $city_id == null)
      {
      if($request->hasParameter("latitude")) {
      $latitude = $request->getParameter("latitude", null);
      if(empty($latitude)) {
        $gps = epMobileGps::factory($this->getUser()->getAgent(), $request->getGetParameters());
        $latitude = $gps->getLatitude();
      }
      }
      if($request->hasParameter("longitude")) {
      $longitude = $request->getParameter("longitude", null);
      if(empty($longitude)) {
        $gps = epMobileGps::factory($this->getUser()->getAgent(), $request->getGetParameters());
        $longitude = $gps->getLongitude();
      }
      }
      if($keyword != null)
          $shop_name = $keyword;
      else
          $shop_name = null;
       if($budget_time != null){
          $budget_time = $request->getParameter("budget_time");
          if($bmin == null){
            $bmin = 0;
          }
          if($bmax == null){
            $bmax = 99999;
          }
       }else{
          $budget_time = null;
          $bmin = null;
          $bmax = null;
       }

        $big_cat_id = null;
        $mid_cat_id = null;
       if($shop_big_category_id != null && $shop_mid_category_id != null){
           $big_cat_id = null;
           $mid_cat_id = $shop_mid_category_id;
       }
       if($shop_big_category_id != null && $shop_mid_category_id == null){
           $big_cat_id = $shop_big_category_id;
           $mid_cat_id = null;
       }
       if($shop_big_category_id == null && $shop_mid_category_id != null){
           $big_cat_id = null;
           $mid_cat_id = $shop_mid_category_id;
       }
        if($dummy_gps_flg == 1) {
          $latitude = 35.6895281;
          $longitude = 139.7006271;
        }
       $pager = $this->getShopListPager($xlCategoryId, null, $latitude, $longitude, $budget_time, $bmin, $bmax, $shop_name, null, $big_cat_id, $mid_cat_id, $page, false, $checkin, $dummy_gps_flg);
      }

   //search by prefecture_id----> This is called when prefecture_id is set and city_id is not set (search by keyword, budget also considered.)
    elseif($prefecture_id != null && $city_id == null){
      if(!preg_match('/^[1-9][0-9]*$/', $prefecture_id) || $prefecture_id < 1 || $prefecture_id > 47){
          throw new cardbook400BadRequestException("prefecture_id::Invalid\tprefecture_idが不正");
      }
      $prefecture = PrefecturePeer::retrieveByPK($prefecture_id);
      $prefName = $prefecture->getName();
      $address = $prefName;
      if($keyword != null)
          $shop_name = $keyword;
      else
          $shop_name = null;
       if($budget_time != null){
          $budget_time = $request->getParameter("budget_time");
          if($bmin == null){
            $bmin = 0;
          }
          if($bmax == null){
            $bmax = 99999;
          }
       }else{
          $budget_time = null;
          $bmin = null;
          $bmax = null;
       }
       $latitude = null;
       $longitude = null;

       if($shop_big_category_id != null && $shop_mid_category_id != null){
           $big_cat_id = null;
           $mid_cat_id = $shop_mid_category_id;
       }
       if($shop_big_category_id != null && $shop_mid_category_id == null){
           $big_cat_id = $shop_big_category_id;
           $mid_cat_id = null;
       }
       if($shop_big_category_id == null && $shop_mid_category_id != null){
           $big_cat_id = null;
           $mid_cat_id = $shop_mid_category_id;
       }

      $pager = $this->getShopListPager($xlCategoryId, $address, $latitude, $longitude, $budget_time, $bmin, $bmax, $shop_name, null, $big_cat_id, $mid_cat_id, $page, false, $checkin);
    }

    //for search through keyword----> This is called when only keyword is set and prefecture_id, city_id, latitude, longitude are not set. (search by budget is considered)
    elseif ($keyword != null && $latitude == null && $prefecture_id == null  && $city_id == null) {
      $address = null;
      $shop_name = $keyword;
      if($budget_time != null){
          $budget_time = $request->getParameter("budget_time");
          if($bmin == null){
            $bmin = 0;
          }
          if($bmax == null){
            $bmax = 99999;
          }
       }else{
          $budget_time = null;
          $bmin = null;
          $bmax = null;
       }
       $latitude = null;
       $longitude = null;

       if($shop_big_category_id != null && $shop_mid_category_id != null){
           $big_cat_id = null;
           $mid_cat_id = $shop_mid_category_id;
       }
       if($shop_big_category_id != null && $shop_mid_category_id == null){
           $big_cat_id = $shop_big_category_id;
           $mid_cat_id = null;
       }
       if($shop_big_category_id == null && $shop_mid_category_id != null){
           $big_cat_id = null;
           $mid_cat_id = $shop_mid_category_id;
       }

      $pager = $this->getShopListPager($xlCategoryId, $address, $latitude, $longitude, $budget_time, $bmin, $bmax, $shop_name, $city_id, $big_cat_id, $mid_cat_id, $page, false, $checkin);
    }

    //considered when city_id is set (in this case prefecture and gps are ignored)
    elseif($city_id != null){
      $address = null;
      if($keyword != null)
          $shop_name = $keyword;
      else
          $shop_name = null;
       if($budget_time != null){
          $budget_time = $request->getParameter("budget_time");
          if($bmin == null){
            $bmin = 0;
          }
          if($bmax == null){
            $bmax = 99999;
          }
       }else{
          $budget_time = null;
          $bmin = null;
          $bmax = null;
       }
       $latitude = null;
       $longitude = null;

       if($shop_big_category_id != null && $shop_mid_category_id != null){
           $big_cat_id = null;
           $mid_cat_id = $shop_mid_category_id;
       }
       if($shop_big_category_id != null && $shop_mid_category_id == null){
           $big_cat_id = $shop_big_category_id;
           $mid_cat_id = null;
       }
       if($shop_big_category_id == null && $shop_mid_category_id != null){
           $big_cat_id = null;
           $mid_cat_id = $shop_mid_category_id;

       }
      $pager = $this->getShopListPager($xlCategoryId, $address, $latitude, $longitude, $budget_time, $bmin, $bmax, $shop_name, $city_id, $big_cat_id, $mid_cat_id, $page, false, $checkin);
    }
    elseif($city_id == null && $latitude == null && $prefecture_id == null && $keyword == null){
      $address = null;
      $shop_name = null;
       if($budget_time != null){
          $budget_time = $request->getParameter("budget_time");
          if($bmin == null){
            $bmin = 0;
          }
          if($bmax == null){
            $bmax = 99999;
          }
       }else{
          $budget_time = null;
          $bmin = null;
          $bmax = null;
       }
       $latitude = null;
       $longitude = null;

       if($shop_big_category_id != null && $shop_mid_category_id != null){
           $big_cat_id = null;
           $mid_cat_id = $shop_mid_category_id;
       }
       if($shop_big_category_id != null && $shop_mid_category_id == null){
           $big_cat_id = $shop_big_category_id;
           $mid_cat_id = null;
       }
       if($shop_big_category_id == null && $shop_mid_category_id != null){
           $big_cat_id = null;
           $mid_cat_id = $shop_mid_category_id;

       }
      $pager = $this->getShopListPager($xlCategoryId, $address, $latitude, $longitude, $budget_time, $bmin, $bmax, $shop_name, $city_id, $big_cat_id, $mid_cat_id, $page, false, $checkin);
    }

    if(isset($pager)){
        $search_detail = array(
        'total_page'       => $pager->getLastPage(),
        'current_page'     => ($pager->getLastPage()) ? $pager->getPage() : 0,
        'shop_num'         => $pager->getNbResults(),
        'shop_list'        => array(),
      );
        foreach($pager->getResults() as $result){

        $shop_id = $result->getShopCode();
        $shop_id = intval($shop_id);
        //$imageName = $shop->getImageName();
        $group_id = $this->getGroupId($shop_id);

        $check_enabled = false;
        $i_cb_checkin_possible = false;
        $imageName = null;
        $ICbShopInfo = ICbShopInfoPeer::retrieveByPK($shop_id);
        if (!empty($group_id)) {
          $ICbGroupInfo = ICbGroupInfoPeer::retrieveByPK($group_id);
        }
        if (!empty($ICbShopInfo)) {
          if ($ICbShopInfo->getCheckinPointFlg()) {
            $check_enabled = true;
          }
          if ($ICbShopInfo->getShopSearchPhoto()) {
            $imageName = $ICbShopInfo->getShopSearchPhoto();
          } elseif (!empty($ICbGroupInfo)) {
            if ($ICbGroupInfo->getGroupSearchPhoto()) {
              $imageName = $ICbGroupInfo->getGroupSearchPhoto();
            }
          }
        } elseif (!empty($ICbGroupInfo)) {
          if ($ICbGroupInfo->getCheckinPointFlg()) {
            $check_enabled = true;
          }
        }

        if ($check_enabled) {
          if (strlen($this->access_token)!= 0) {
            if($this->getMe()!= null) {
              $shop = ShopPeer::retrieveByPK($shop_id);
              //with seperate settings
              $spmst = $shop->getShopPointMst();

                $now = date('Y-m-d H:i:s');
              $newGetPoint= false;
              if($spmst) {
                $member_id = $this->getMe()->getId();
                $shopmembercard = cardbookFunction::registerShopMemberCard($spmst, $member_id, $shop_id);
                $newGetPoint = ShopMemberPointRecordPeer::getNewAvailableGetPoint($shop_id,$spmst,$member_id,$now);
              } else {
                  $i_cb_checkin_possible = LCbCheckInLogPeer::getCheckInStatus($shop_id, $group_id, $this->getMe()->getId(), date('Y-m-d', time()));
                  $i_cb_checkin_possible ? $check_enabled = true : $check_enabled = false;
              }
              if(is_numeric($newGetPoint)) {
                $i_cb_checkin_possible = true;
              }
            } else {
              throw new cardbook401UnauthorizedException('invalid_token' . "\t" . 'アクセストークンが不正');
            }
          }
          if ($i_cb_checkin_possible) {
            $check_enabled = true;
          } else {
            $check_enabled = false;
          }
        } else {
          if (strlen($this->access_token) != 0) {
            if ($this->getMe() != null) {
              $i_cb_checkin_possible = LCbCheckInLogPeer::getCheckInStatus($shop_id, $group_id, $this->getMe()->getId(), date('Y-m-d', time()));
              $i_cb_checkin_possible ? $check_enabled = true : $check_enabled = false;
            } else {
              throw new cardbook401UnauthorizedException('invalid_token' . "\t" . 'アクセストークンが不正');
            }
          }
        }
        if (!empty($imageName)) {
          //$image = 'https://' . $host . '/uploads/shop_images/' . $image;
          $image = epS3Facade::getImgUrl($imageName, ShopPointMstPeer::getShopPointMstIdByShopId($shop_id, $activeOnly = false), epS3Facade::TYPE_POINT);
        }else{
          $image = null;
        }
        /*if (!empty($shopPhoto)) {
          $image = $request->getUriPrefix2() . $shopPhoto;
        } else {
          $image = null;
        }*/

        $lat1 = $latitude;
        $lon1 = $longitude;
        $lat2 = $result->getLatitude();
        $lon2 = $result->getLongitude();
        if((trim($lat1) != "" || $lat1 != null) && (trim($lon1) != "" || $lon1 != null))
          $distance = intval(epDistance::calculate($lat1,$lon1,$lat2,$lon2));
        else
          $distance = null;
            $shop_lists = array(
                'group_id'   => $group_id,
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
                'is_pos'     => TCardbookShopPeer::IS_POS_ENABLED,
                'flg_checkin' => $checkin == self::CHECKIN ? $check_enabled == true ? 1 : 0 : 0,
            );

            array_push($search_detail['shop_list'], $shop_lists);
        }
    }
    return $this->renderText(json_encode($search_detail));
  }

  //returns pager as per set condition
  public static function getShopListPager($xlCategoryId, $address, $latitude, $longitude, $budget_time, $bmin, $bmax, $shop_name, $city_id, $big_cat_id,
                                          $mid_cat_id, $page, $onlyPublicationFlag = NULL, $checkin, $dummy_gps_flg = 0)
  {
    $criteria = self::getShopSearchCriteriaFromSpSearchCriteria($xlCategoryId, $address, $latitude, $longitude, $budget_time, $bmin, $bmax, $shop_name, $city_id, $big_cat_id, $mid_cat_id, $onlyPublicationFlag, $checkin, $dummy_gps_flg);
    return epPropelPagerFactory::createPropelPager('ShopSearch', self::DISPLAY_LIMIT, $criteria, $page, "doSearch", "doSearchCount");
  }

  //considerd for search through prefecture value and keyword
  public static function getShopSearchCriteriaFromSpSearchCriteria($xlCategoryId, $address, $latitude, $longitude, $budget_time, $bmin, $bmax, $shop_name, $city_id, $big_cat_id, $mid_cat_id, $onlyPublicationFlag = NULL, $checkin, $dummy_gps_flg)
  {
    //EPCDB-766
    $criteria = new Criteria();
    $criteria->clearSelectColumns();
    $criteria->addJoin(ShopSearchPeer::SHOP_CODE, ShopAuthConfigPeer::SHOP_ID, Criteria::LEFT_JOIN);
    $criteria->addJoin(ShopSearchPeer::SHOP_CATEGORY_ID, ShopCategoryPeer::ID, Criteria::LEFT_JOIN);
    $criteria = epMemberFacade::appendIsMemberShipShopCriteria($criteria);

    self::addSelectAndGroupByColumnsForSearch($criteria);
    $criteria->addSelectColumn("GROUP_CONCAT(DISTINCT ".ShopCategoryPeer::NAME." SEPARATOR ' ') AS shop_category_names");
    $criteria->add(ShopSearchPeer::XL_CATEGORY_ID, $xlCategoryId, Criteria::EQUAL);
    $criteria->add(ShopSearchPeer::TIMESAVING_FLG, ShopSearchPeer::TIMESAVING_FLG_TRUE, Criteria::EQUAL);
    $criteria->add(ShopSearchPeer::SERVICE_CODE, poServiceFacade::SERVICE_CODE_TM, Criteria::NOT_EQUAL);

    if ($checkin) {
      $criteria->addJoin(ShopSearchPeer::SHOP_CODE, ICbShopInfoPeer::SHOP_ID, Criteria::LEFT_JOIN);
      $criteria->add(ICbShopInfoPeer::CHECKIN_FLG, self::FLAG_CHECKIN_TRUE, Criteria::EQUAL);
      $criteria->addJoin(ShopPointMstPeer::GROUP_ID, ICbGroupInfoPeer::GROUP_ID, Criteria::LEFT_JOIN);
      $cri1 = $criteria->getNewCriterion(ICbGroupInfoPeer::CHECKIN_FLG, self::FLAG_CHECKIN_TRUE, Criteria::EQUAL);
      $cri2 = $criteria->getNewCriterion(ICbGroupInfoPeer::CHECKIN_FLG, NULL, Criteria::EQUAL);
      $cri1->addOr($cri2);
      $c1 = $criteria->getNewCriterion(ICbShopInfoPeer::CHECKIN_FLG, self::FLAG_CHECKIN_TRUE, Criteria::EQUAL);
      $cri1->addAnd($c1);
      $c3 = $criteria->getNewCriterion(ICbGroupInfoPeer::CHECKIN_FLG, self::FLAG_CHECKIN_FALSE, Criteria::EQUAL);
      $c4 = $criteria->getNewCriterion(ICbShopInfoPeer::CHECKIN_FLG, self::FLAG_CHECKIN_TRUE, Criteria::EQUAL);
      $c3->addAnd($c4);
      $cri1->addOr($c3);
      $criteria->addAnd($cri1);
    }
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
      if(!$dummy_gps_flg) {
        $criteria->add(ShopSearchPeer::LATITUDE, $custom, Criteria::CUSTOM);
      }

    }

    if(isset($big_cat_id) && mb_strlen($big_cat_id) > 0){
        $criteria->add(ShopSearchPeer::BIG_CATEGORY_ID, $big_cat_id, Criteria::EQUAL);
    }
    if(isset($mid_cat_id) && mb_strlen($mid_cat_id) > 0){
        $criteria->add(ShopSearchPeer::MID_CATEGORY_ID, $mid_cat_id, Criteria::EQUAL);
    }
    if(isset($city_id) && mb_strlen($city_id) > 0){
        $criteria->add(ShopSearchPeer::CITY_ID, $city_id, Criteria::EQUAL);
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

    if (isset($budget_time) && $budget_time > 0) {

      // 昼・夜
      $target = $budget_time == self::BUDGET_TIME_LUNCH ? ShopSearchPeer::UNIT_PRICE_LUNCH : ShopSearchPeer::UNIT_PRICE_DINNER;

      // 同一の値が選択されている場合
      if ($bmin == $bmax) {
        // 検索値
        $unitPrice = null;

        // 下限を基準に上限はひとつ上の値で検索
        switch ($bmin) {
          case self::BUDGET_MIN_1000:
            $unitPrice = ShopSearchPeer::UNIT_PRICE_LUNCH_2;
            break;
          case self::BUDGET_MIN_2000:
            $unitPrice = ShopSearchPeer::UNIT_PRICE_LUNCH_3;
            break;
          case self::BUDGET_MIN_3000:
            $unitPrice = ShopSearchPeer::UNIT_PRICE_LUNCH_4;
            break;
          case self::BUDGET_MIN_5000:
            $unitPrice = ShopSearchPeer::UNIT_PRICE_LUNCH_5;
            break;
          case self::BUDGET_MIN_10000:
            $unitPrice = ShopSearchPeer::UNIT_PRICE_LUNCH_6;
            break;
          case self::BUDGET_MIN_20000:
            $unitPrice = ShopSearchPeer::UNIT_PRICE_LUNCH_7;
            break;
        }

        // EQUAL
        $criteria->add($target, $unitPrice, Criteria::EQUAL);
      }
      // 範囲
      else {
        $unitPrices = array();

        // 下限
        switch (true) {
          case $bmin == self::BUDGET_MIN_NONE:
            $unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_0] = ShopSearchPeer::UNIT_PRICE_LUNCH_0;
            $unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_1] = ShopSearchPeer::UNIT_PRICE_LUNCH_1;
          case $bmin <= self::BUDGET_MIN_1000:
            $unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_2] = ShopSearchPeer::UNIT_PRICE_LUNCH_2;
          case $bmin <= self::BUDGET_MIN_2000:
            $unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_3] = ShopSearchPeer::UNIT_PRICE_LUNCH_3;
          case $bmin <= self::BUDGET_MIN_3000:
            $unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_4] = ShopSearchPeer::UNIT_PRICE_LUNCH_4;
          case $bmin <= self::BUDGET_MIN_5000:
            $unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_5] = ShopSearchPeer::UNIT_PRICE_LUNCH_5;
          case $bmin <= self::BUDGET_MIN_10000:
            $unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_6] = ShopSearchPeer::UNIT_PRICE_LUNCH_6;
          case $bmin <= self::BUDGET_MIN_20000:
            $unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_7] = ShopSearchPeer::UNIT_PRICE_LUNCH_7;
        }

        // 上限
        switch (true) {
          case $bmax <= self::BUDGET_MAX_1000:
            unset($unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_2]);
          case $bmax <= self::BUDGET_MAX_2000:
            unset($unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_3]);
          case $bmax <= self::BUDGET_MAX_3000:
            unset($unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_4]);
          case $bmax <= self::BUDGET_MAX_5000:
            unset($unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_5]);
          case $bmax <= self::BUDGET_MAX_10000:
            unset($unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_6]);
          case $bmax <= self::BUDGET_MAX_20000:
            unset($unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_0]);
            unset($unitPrices[ShopSearchPeer::UNIT_PRICE_LUNCH_7]);
        }

        // 全選択でない場合のみ（下限無し～上限無しの場合以外）
        if (count($unitPrices) < count(ShopSearchPeer::getUnitPriceLunchList())) {
          // INで指定
          $criteria->add($target, $unitPrices, Criteria::IN);
        }
      }
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

/**
 * @version : phase6 (Cardbook #EPCDB-1026)
 * @author：ビベーク
 * @日付 ：｛2015/12/11｝
 * @param：無い場合： なし
 * @return : member Object
 */
  public function createMe()
  {
    $accessTokenData = $this->getOAuthServer()->getAccessTokenData(
    // @todo ヘッダからアクセストークンを取り出しリクエストインスタンスにセット
      OAuth2_Request::createFromGlobals(),
      new OAuth2_Response()
    );
    $userId = $accessTokenData['user_id'];
    $member = MemberPeer::findActiveMemberById($userId);
    if(!$member){
    }
    return $member;
  }

}
