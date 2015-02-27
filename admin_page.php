<div class="wrap">

<textarea cols="80" rows="100">

<?php

/*
	Series (categories in WordPress)
*/
$json_categories = array();
$categories = get_categories(array('hide_empty' => 0, 'orderby' => 'ID'));
$sequential_cat_id = 1;
foreach ($categories as $category) {
  $category->sequential_cat_id = $sequential_cat_id++;
  $json_categories[] = array("id" => $category->sequential_cat_id, 'name' => $category->name, 'slug' => $category->slug, 'description' => $category->description);
  //echo $template;
}

/*
	Episodes
*/
$json_episodes = array();
$episodes = array();
$episode_args = array('post_type'=>'episode', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC');
$sequential_episode_id = 1;
$query = new WP_Query($episode_args);
while($query->have_posts()) { $query->the_post();
  $seid = $sequential_episode_id++;
  $post = $query->post;

  $title = $post->post_title;
  $description = $post->post_excerpt;
  $content = $post->post_content;
  $post_categories = get_the_category($post->ID);
  $post_category = $post_categories[0];

  $target_category = array_filter($categories, function($obj) use ($post_category) {
   if ($post_category->cat_ID == $obj->cat_ID) {return true;} return false;
  });

  $sequential_cat_id = end($target_category)->sequential_cat_id;
  
  $meta = array('id' => $post->ID);
  $json_episodes[] = array('id' => $seid, 'name' => $title, 'series_id' => $sequential_cat_id, 'created_at' => $post->post_date, 'updated_at' => $post->post_modified, 'meta' => $meta);

}

/*
	Episode Relations - parent, frigne
*/
$json_episode_relations = array();
function translate_episode_id($episodes, $wp_id) {
  $episodes = array_filter($episodes, function($obj) use ($wp_id) {
   if ($obj['meta']['id'] == $wp_id) {return true;} return false;
  });
  $target_episode = array_pop($episodes);
  return $target_episode;
}

$episode_args = array('post_type'=>'episode', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC');
$query = new WP_Query($episode_args);
$seq_id_er = 1;
while($query->have_posts()) { $query->the_post();
  $post = $query->post;
  $sid = $seq_id_er++;
  $episode = Nexus_Episode::factory($post);
  if ( $episode->has_fringe() ) { $related = Nexus_Episode::factory($episode->get_fringe()); $rep = translate_episode_id($json_episodes, $related->get_id());   $json_episode_relations[] = array('type' => 'fringe', 'episode' => $sid, 'related' => $rep['id']); }  
  if ( $episode->has_parent() ) { $related = Nexus_Episode::factory($episode->get_parent()); $rep = translate_episode_id($json_episodes, $related->get_id()); $json_episode_relations[] = array('type' => 'parent', 'episode' => $sid, 'related' => $rep['id']); }  
}

/*
	People
*/
$json_people = array();
$people_args = array('post_type'=>'person', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC');
$query = new WP_Query($people_args);
$sequential_person_id = 1;
while($query->have_posts()) { $query->the_post();
  $post = $query->post;
 
  $email = get_post_meta($post->ID, 'nexus-people-email', true);

  $spid = $sequential_person_id++;

  $meta = array('id' => $post->ID);
  $json_people[] = array('id' => $spid, 'name' => $post->post_title, 'content' => $post->post_content, 'email' => $email, 'meta' => $meta);
}

/*
	People Relations - host, guest
*/
$json_people_relations = array();

$episode_args = array('post_type'=>'episode', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC');
$query = new WP_Query($episode_args);
$seq_id_er = 1;
while($query->have_posts()) { $query->the_post();
  $post = $query->post;
  $sid = $seq_id_er++;
  $episode = Nexus_Episode::factory($post);

  if ( !$episode->has_people() ) {continue;}

  $people = $episode->get_people();
  
  foreach ($people as $type => $persons) {
   foreach ($persons as $person) {
     $po = Nexus_Person::factory($person);
     $rep = translate_episode_id($json_people, $po->get_id());
     $json_people_relations[] = array('role' => ($type == 'hosts' ? 'host' : 'guest'), 'person_id' => $rep['id'], 'episode_id' => $sid);
    }
  }
}

// the end

$json_output = array();
$json_output['series'] = $json_categories;
$json_output['episodes'] = $json_episodes;
$json_output['episode_relations'] = $json_episode_relations;
$json_output['people'] = $json_people;
$json_output['people_relations'] = $json_people_relations;

echo json_encode($json_output, JSON_PRETTY_PRINT);

?>

</textarea>

</div>