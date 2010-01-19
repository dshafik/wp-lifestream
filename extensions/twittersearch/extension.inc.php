<?php
class Lifestream_TwitterSearchFeed extends Lifestream_Feed
{
	const ID			= 'twittersearch';
	const NAME			= 'Twitter Search';
	const URL			= 'http://search.twitter.com/';
	const DESCRIPTION	= 'Specify search terms on which to retrieve tweets (see: <a href="http://search.twitter.com/operators">http://search.twitter.com/operators</a>)';
	const LABEL		= 'Lifestream_MessageLabel';
	const CAN_GROUP	= false;

	function __toString()
	{
		return $this->options['search'];
	}

	function get_options()
	{		
		return array(
			'search' => array($this->lifestream->__('Search Terms:'), true, '', ''),
		);
	}
	
	function _get_user_link($match)
	{
		return $match[1].$this->get_user_link($match[2]);
	}
	
	function _get_search_term_link($match)
	{
		return $match[1].$this->lifestream->get_anchor_html(htmlspecialchars($match[2]), 'https://search.twitter.com/search?q='.urlencode($match[2]), array('class'=>'searchterm'));
	}

	function get_user_link($user)
	{
		return $this->lifestream->get_anchor_html('@'.htmlspecialchars($user), $this->get_user_url($user), array('class'=>'user'));
	}
	
	function get_user_url($user)
	{
		return 'http://www.twitter.com/'.urlencode($user);
	}
	
	function get_public_url()
	{
		return $this->get_user_url($this->options['username']);
	}

	function parse_users($text)
	{
		return preg_replace_callback('/([^\w]*)@([a-z0-9_\-\/]+)\b/i', array($this, '_get_user_link'), $text);
	}

	function parse_search_term($text)
	{
		return preg_replace_callback('/([^\w]*)(#[a-z0-9_\-\/]+)\b/i', array($this, '_get_search_term_link'), $text);
	}

	function get_url($page=1, $count=20)
	{
		$url_base = 'http://search.twitter.com/search.atom?rpp=' .$count. '&page=' .$page. '&q=';
		return $url_base . rawurlencode($this->options['search']);
	}
	
	function save()
	{
		$is_new = (bool)!$this->id;
		parent::save();
		if ($is_new)
		{
			// new feed -- attempt to import all statuses up to 500
			$feed_msg = array(true, '');
			$page = 0;
			while ($feed_msg[0] !== false && $page < 10)
			{
				$page += 1;
				$feed_msg = $this->refresh($this->get_url($page, 50));
			}
		}
	}
	
	function render_item($row, $item)
	{
		return "{$item['user']} <em>&raquo;</em> " . $item['description'] . " <span style='font-size: 80%'>[<a href='{$item['link']}'>link</a>]</span>"; // $this->parse_search_term($this->parse_users($this->parse_urls(htmlspecialchars($item['description'])))) . ' ['.$this->lifestream->get_anchor_html(htmlspecialchars($this->options['username']), $item['link']).']';
	}
	
	function yield($row, $url, $key)
	{
		static $i = 1;
		$data = parent::yield($row, $url, $key);
		$description = $this->lifestream->html_entity_decode($row->get_description());
		$author = $this->get_author($row);
		$data['user'] = $author['user'];
		$data['name'] = $author['name'];
		$data['image'] = $author['image'];
		$data['description'] = $description;
		return $data;
	}
	
	function get_author($row)
	{
		$matches = array();
		$a = $row->get_author();
		preg_match("/(.*?) \((.*?)\)/", $a->name, $matches);
		$author['name'] = $matches[2];
		$author['user'] = $matches[1];
		$author['image'] = $row->get_link(0, 'image');
		
		return $author;
	}
}


$lifestream->register_feed('Lifestream_TwitterSearchFeed');
?>