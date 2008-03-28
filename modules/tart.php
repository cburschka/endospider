<?php

function page_tart_new($nation) {
  $page->title = t('Which nations should @nation endorse?', array('@nation' => n($nation)));
  $region = db_read('nation', array('region'), array('nation' => $nation));
  $all_nations = db_read('nation', array('nation', 'active'), array('region' => $region));
  foreach ($all_nations as $i => $n) {
    $all_nations[$i] = $n['nation'];
    $active[$n['nation']] = (28 * 86400 - $n['active']) / 28 / 86400;
  }
  $endorsed = db_read('endorsement', array('receiving'), array('giving' => $nation));
  $endorsing = db_read('endorsement', array('giving'), array('receiving' => $nation));
  
  $all_nations = array_diff($all_nations, array($nation));
  $not_endorsed = array_diff($all_nations, $endorsed);
  $not_endorsing = array_diff($all_nations, $endorsing);
  $not_endorsed = array_intersect($not_endorsed, $not_endorsing);
  
  foreach ($not_endorsed as $candidate) {
    $my_received = db_read('endorsement', array('giving'), array('receiving' => $candidate));
    $my_given = db_read('endorsement', array('receiving'), array('giving' => $candidate));
    if (!is_array($my_received)) $my_received = array($my_received);
    if (!is_array($my_given)) $my_given = array($my_given);
    $my_returned = array_intersect($my_received, $my_given);
    
    $returned[$candidate] = count($my_returned);
    $received[$candidate] = count($my_received);
    $given[$candidate] = count($my_given);
    $score[$candidate] = $returned[$candidate] / $received[$candidate] * sqrt($returned[$candidate] / $given[$candidate]) * sqrt($active[$candidate]);
  }
  
  arsort($score);
  
  foreach ($score as $candidate => $my_score) {
    $rows[] = array(
      'nation' => l("http://nationstates.net/$candidate", n($candidate)),
      'given' => $given[$candidate],
      'received' => $received[$candidate],
      'returned' => sprintf('%.2f', $my_score * 100). '% ('. $returned[$candidate] .')',
    );
  }
  
  $header = array(
    'nation' => t('Nation'),
    'given' => t('Outgoing'),
    'received' => t('Incoming'),
    'returned' => t('Rate of Return'), 
  );
  $page->content = '<p>'. t("This view takes into account the various nations' endorsement behavior and activity. The highest-ranked nations" .
  " are those that endorse everyone who endorses them, and noone else, and that were active very recently. Nations that hand out many endorsements 
  that are not returned (tarters) are penalized as their uncommonly high RoR falsely implies they pay attention to incoming endorsements when it actually has 
  little influence on their tarting behavior. In fact, endorsing them might make them pass you by - they only endorse people who have not yet endorsed them. 
  Inactive nations are penalized for obvious reasons.") .'</p>';
  $page->content .= html_table($header, $rows);
  return $page;
}

function page_tart_remove($nation) {
  $page->title = t('Which nations should @nation withdraw their endorsement from?', array('@nation' => $nation));
}
