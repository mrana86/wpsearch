<?php
// SELECT DISTINCT
//   p.ID            AS id,
//   p.post_title    AS title,
//   p.post_author   AS user,
//   p.post_content  AS content,
//   p.post_excerpt  AS intro,
//   p.post_status   AS status,
//   p.post_date     AS created_at,
//   p.post_modified AS updated_at,
//   (SELECT group_concat(p.guid SEPARATOR ', ')
//    FROM mvai2_postmeta pm
//      LEFT JOIN mvai2_posts p ON pm.meta_value = p.ID
//    WHERE pm.post_id = 17917 AND pm.meta_key = '_thumbnail_id' AND p.post_type = 'attachment'
//   )               AS image,
//   (SELECT group_concat(pm.meta_value SEPARATOR ', ')
//    FROM mvai2_posts p
//      LEFT JOIN mvai2_postmeta pm ON pm.meta_key = 'views' AND pm.post_id = p.ID
//    WHERE p.ID = 17917
//   )               AS views,
//   (SELECT group_concat(t.name SEPARATOR ', ')
//    FROM mvai2_terms t
//      LEFT JOIN mvai2_term_taxonomy tt ON t.term_id = tt.term_id
//      LEFT JOIN mvai2_term_relationships tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
//    WHERE tt.taxonomy = 'category' AND p.ID = tr.object_id
//   )               AS category,
//   (SELECT group_concat(t.name SEPARATOR ', ')
//    FROM mvai2_terms t
//      LEFT JOIN mvai2_term_taxonomy tt ON t.term_id = tt.term_id
//      LEFT JOIN mvai2_term_relationships tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
//    WHERE tt.taxonomy = 'post_tag' AND p.ID = tr.object_id
//   )               AS tag
// FROM mvai2_posts p
// WHERE p.post_type = 'post'
// ORDER BY p.post_date DESC
// LIMIT 30


function db_connect($host,$user,$pass,$db) {
   $mysqli = new mysqli($host, $user, $pass, $db);
   $mysqli->set_charset("utf8");
   if($mysqli->connect_error) 
     die('Connect Error (' . mysqli_connect_errno() . ') '. mysqli_connect_error());
   return $mysqli;
}
$mysqli = db_connect('localhost','root','','wp-search');

function get_category($post_id){
    global $mysqli;
    $sql1 = "SELECT t.* FROM `mvai2_terms` t JOIN `mvai2_term_taxonomy` tt ON(t.`term_id` = tt.`term_id`) JOIN `mvai2_term_relationships` ttr ON(ttr.`term_taxonomy_id` = tt.`term_taxonomy_id`) WHERE tt.`taxonomy` = 'category' AND ttr.`object_id` = ".$post_id;
    $result = $mysqli->query($sql1);
    $data = $result->fetch_assoc();
    return $data['name'];
}
function get_tags($post_id){
    global $mysqli;
    $sql1 = "SELECT DISTINCT
  p.ID            AS id,
  p.post_title    AS title,
  p.post_author   AS user,
  p.post_content  AS content,
  p.post_status   AS status,
  (SELECT group_concat(t.name SEPARATOR ', ')
   FROM mvai2_terms t
     LEFT JOIN mvai2_term_taxonomy tt ON t.term_id = tt.term_id
     LEFT JOIN mvai2_term_relationships tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
   WHERE tt.taxonomy = 'post_tag' AND p.ID = tr.object_id
  )               AS tag
FROM mvai2_posts p
WHERE p.ID = ".$post_id;
    $result = $mysqli->query($sql1);
    $data = $result->fetch_assoc();
    return $data['tag'];
}

// Get active posts list
$sql = "SELECT * FROM mvai2_posts where post_status = 'publish'";
$result = $mysqli->query($sql);

$posts_array = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if($row['post_type'] == 'post'){
          $row['category'] = get_category($row['ID']); 
          $row['tag'] = get_tags($row['ID']); 
          }else{
            $row['category'] = '';
            $row['tag'] = '';
          }        
        $posts_array[] = $row;
    }
}
$mysqli->close();
// Prepare string for suggest
$suggest_output = '';
if(!empty($posts_array)){
    foreach ($posts_array as $key => $row) {
        $post_title = str_replace("'", "\'", $row['post_title']);
        $suggest_output.= "'$post_title',";
    } 
}
$q = '';
if(!empty($_POST['search'])){
    $q = $_POST['search'][0];
}
print_r($q);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Wordpress database search without install</title>
        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet">
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
        <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
        <!-- magicsuggest -->
        <link href="magicsuggest/magicsuggest-min.css" rel="stylesheet">
        <script src="magicsuggest/magicsuggest-min.js"></script>
        <!-- Custom css -->
        <link href="style.css" rel="stylesheet" id="custom-css"></link>       
    </head
    <body>
        <div class="container">
        	<div class="row">
                <div class="col-sm-3">&nbsp;</div>
                <div class="col-sm-6">
                    <div class="search-container"> 
                        <form id="searchPage" autocomplete="off" action="" method="post">
                            <div class="input-group stylish-input-group">
                                <input id="magicsuggest" value="<?php echo $q;?>" name="search[]" type="text" class="form-control" placeholder="Search" >
                                <span class="input-group-addon">
                                    <button type="submit">
                                        <span class="glyphicon glyphicon-search"></span>
                                    </button>  
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-sm-3">&nbsp;</div>
        	</div>
            <?php if(!empty($posts_array)){?>            
                <?php foreach ($posts_array as $key => $row) {?>
                <div class="row">
                    <div class="col-sm-3">&nbsp;</div>
                    <div class="col-sm-6">
                        <div class="search-content">
                            <h2><?php echo $row['post_title'];?></h2>
                            <p><?php echo $row['post_content'];?></p>
                            <?php if(!empty($row['category'])){?>
                                <span class="cat-name"><b>Category : </b><?php echo $row['category'];?></span>
                            <?php } ?>                           
                            <span class="date"><b>Date : </b><?php echo $row['post_date'];?></span>
                            <?php if(!empty($row['tag'])){?>
                                <span class="tags"><b>Tags : </b><?php echo $row['tag'];?></span>
                            <?php } ?>
                            <a class="btn btn-primary read-more" target="_blank" href="<?php echo $row['guid'];?>">Read More </a>
                        </div>
                    </div>
                    <div class="col-sm-3">&nbsp;</div>
                </div>
                <?php } ?>            
            <?php } ?>
        </div>
        <script type="text/javascript">
            $(function() {
                $('#magicsuggest').magicSuggest({
                    allowFreeEntries: true,
                    autoSelect : true,
                    hideTrigger: true,
                    expandOnFocus: false,
                    maxSelection: 1,
                    maxDropHeight: 200,
                    data: [<?php echo $suggest_output;?>]
                });
                //Auto search form submit
                $(document).on('click','.ms-res-item',function(){
                  //$("#searchPage").submit();
                });
            });
        </script>
    </body>
</html>