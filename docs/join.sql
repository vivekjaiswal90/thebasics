SELECT spm.id, spm2.id, spm.group_id spm_gid, gs.group_id gs_gid, spm2.group_id spm2_gid, s.id sid, spm.shop_id spm_sid, spm2.shop_id spm2_sid, s.name FROM shop_search AS ss LEFT JOIN shop_point_mst AS spm ON ss.shop_code = spm.shop_id LEFT JOIN shop AS s ON ss.shop_code = s.id LEFT JOIN group_shop AS gs ON s.id = gs.shop_id LEFT JOIN shop_point_mst AS spm2 ON spm2.group_id = gs.group_id WHERE ss.keywords LIKE '%インド%';
-- SELECT ss.keywords FROM shop_search ss WHERE ss.keywords LIKE '%インド%';






to edit database.yml

 $username = guess_username();
-$all_dbname_prefix  = get_database_name_prefix('all');
-$dev_dbname_prefix  = get_database_name_prefix('dev');
-$test_dbname_prefix = get_database_name_prefix('test');
+//$all_dbname_prefix  = get_database_name_prefix('all');
+//$dev_dbname_prefix  = get_database_name_prefix('dev');
+//$test_dbname_prefix = get_database_name_prefix('test');
+$all_dbname_prefix  = 'dev_white_';
+$dev_dbname_prefix  = 'dev_white_';
+$test_dbname_prefix = 'test_white_';
