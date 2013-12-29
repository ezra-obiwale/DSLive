<?php

use DScribe\Core\Engine;

/*
 */

/**
 * Description of Page
 *
 * @author topman
 */
class Page {

	public static function carousel($table, array $criteria = array()) {
		$media = Engine::getDB()->table($table)->select($criteria);
		$items = array();
		$fo = new FileOut();
		if ($media && !is_bool($media)) {
			foreach ($media as $key => $medium) {
				$items[] = array(
					'img' => $fo($medium->file, array(
						'attrs' => array(
							'style' => 'height:340px;width:100%',
						)
					)),
					'caption' => '<h4>' . $medium->title . '</h4><p>' . $medium->description . '</p>',
					'active' => ($key === 0),
				);
			}
		}
		return TwBootstrap::carousel($items, array('class' => 'span12'));
	}

	public static function social(array $links) {
		$return = '<ul class="social">';
		foreach ($links as $label => $link) {
			if (strtolower(substr($link, 0, 7)) !== 'http://' && strtolower(substr($link, 0, 8)) !== 'https://')
				$link = 'http://' . $link;
			$return .= '<li><a target="_blank" href="' . $link . '">' . $label . '</a></li>';
		}
		return $return . '</ul>';
	}

}
