<?php

namespace app\models\amazon;

use Yii;
use yii\base\Model;

/**
 * Product is the model behind the analyzer-form.
 */
class Product extends Model
{
	public $domain;
	public $page_not_found;
	
    public $asin;
	public $parent_asin;
	public $title;
	public $price;
	public $last_update;
	public $salesrank;
	public $brand;
	public $size;
	public $color;
	public $ean;
	public $similarproducts;
	public $rankings;
	public $visindex;
	
	public $product_title;
	public $bullet_points;
	public $product_description;
	public $product_images;
	public $average_rating;
	public $reviews;
	public $bestseller_rank;
	public $category;
	public $prime;
	
	public $kpi__amount_of_reviews;
	public $kpi__length_of_title;
	public $kpi__amount_of_bullet_points;
	public $kpi__length_of_each_bullet_point;
	public $kpi__length_of_description;
	
	public $kpi_status__amount_of_reviews;
	public $kpi_status__length_of_title;
	public $kpi_status__amount_of_bullet_points;
	public $kpi_status__length_of_each_bullet_point;
	public $kpi_status__length_of_description;
	
	/**
	* @return array customized attribute labels
	*/
	public function attributeLabels()
	{
		return [
		'asin' => 'ASIN',
		'domain' => 'Domain',
		];
	}
	
	public function rules(){
		return [
			['asin', 'required'],
			['asin', 'string', 'min' => 10, 'max'=>10],				
			['asin', 'match', 'pattern' => '/^[a-zA-Z0-9\s]+$/',],				
			['domain', 'required'],
		];
	}
	
	public function informations(){
		$this->get_asin();
		$this->get_page();
		if(!$this->page_not_found){
			$this->calculateKPIs();
			$this->analyzeKPIs();
		}
	}
	
	public function calculateKPIs(){
		$this->kpi__amount_of_reviews = $this->reviews;
		$this->kpi__length_of_title = strlen($this->product_title);
		/*
		$dom = new \DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTML($this->bullet_points);
		$this->kpi__amount_of_bullet_points = $dom->getElementsByTagName('li')->length;
		*/
		$this->kpi__amount_of_bullet_points = count($this->bullet_points);
		
		$bullet_point_array = array();
		/*
		foreach($dom->getElementsByTagName('li') as $li){
			 $bullet_point_array[] = strlen($li->nodeValue);
		}
		*/
		foreach($this->bullet_points as $li){
			 $bullet_point_array[] = strlen(trim($li));
		}
		$this->kpi__length_of_each_bullet_point = $bullet_point_array;
		
		$this->kpi__length_of_description = strlen($this->product_description);
	}
	
	public function analyzeKPIs(){
		if($this->kpi__amount_of_reviews < 20)
			$this->kpi_status__amount_of_reviews = false;
		else
			$this->kpi_status__amount_of_reviews = true;
		
		if($this->kpi__length_of_title < 100 or $this->kpi__length_of_title > 200)
			$this->kpi_status__length_of_title = false;
		else
			$this->kpi_status__length_of_title = true;
		
		if($this->kpi__amount_of_bullet_points < 5 )
			$this->kpi_status__amount_of_bullet_points = false;
		else
			$this->kpi_status__amount_of_bullet_points = true;
		
		$this->kpi_status__length_of_each_bullet_point = true;
		foreach($this->kpi__length_of_each_bullet_point as $li){
			if($li < 150 or $li > 300)
				$this->kpi_status__length_of_each_bullet_point = false;
		}
		
		
		if($this->kpi__length_of_description < 1500 )
			$this->kpi_status__length_of_description = false;
		else
			$this->kpi_status__length_of_description = true;
		
	}
	
	public function get_asin(){
		$uri = 'http://app.amzrank.net/de/api/get_asin?apikey=3bKaWgUxUiryCcGWCaUapARg&rankings=true&asin=' . $this->asin;
		$ch = curl_init($uri);
		curl_setopt_array($ch, array(
			CURLOPT_HTTPHEADER  => array('Authorization: ' . rand()),
			CURLOPT_RETURNTRANSFER  =>true,
			CURLOPT_VERBOSE     => 1
		));
		$out = curl_exec($ch);
		curl_close($ch);
		$out = json_decode($out);
		$this->parent_asin = (empty($out->data->parent_asin) ? "NA": $out->data->parent_asin);
		$this->title = (empty($out->data->title) ? "NA": $out->data->title);
		$this->price = (empty($out->data->price) ? "NA": $out->data->price);
		$this->last_update = (empty($out->data->last_update) ? "NA": $out->data->last_update);
		$this->salesrank = (empty($out->data->salesrank) ? "NA": $out->data->salesrank);
		$this->brand = (empty($out->data->brand) ? "NA": $out->data->brand);
		$this->size = (empty($out->data->size) ? "NA": $out->data->size);
		$this->color = (empty($out->data->color) ? "NA": $out->data->color);
		$this->ean = (empty($out->data->ean) ? "NA": $out->data->ean);
		$this->similarproducts = (empty($out->data->similarproducts) ? "NA": $out->data->similarproducts);
		$this->rankings = (!isset($out->data->rankings) ? array(): $out->data->rankings);
		$this->visindex = (empty($out->data->visindex) ? "NA": $out->data->visindex);
	}
	
	public function get_dom(){
	}
	
	public function get_page(){
		$uri = 'https://www.amazon.' . $this->domain . '/dp/' . $this->asin;
		$ch = curl_init($uri);
		curl_setopt_array($ch, array(
			CURLOPT_HTTPHEADER  => array('Authorization: ' . rand()),
			CURLOPT_RETURNTRANSFER  =>true,
			CURLOPT_VERBOSE     => 1,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_FAILONERROR => 1,
			CURLOPT_COOKIESESSION => 1,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_USERAGENT =>  'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3',
			CURLOPT_CONNECTTIMEOUT => 120,
			CURLOPT_TIMEOUT => 120
		));
			
		$html = curl_exec($ch);  // Executing cURL session					
		curl_close($ch);    // Closing cURL session
		
		# Create a DOM parser object
		$dom = new \DOMDocument();

		$this->page_not_found = true;
		if(empty($html)){
			$this->page_not_found = true;
		}else{
			libxml_use_internal_errors(true);
			$html_without_null_chars = str_replace("\0", '', $html);
			$dom->loadHTML($html_without_null_chars);
			$this->page_not_found = ($this->queryIsThere($html, "Page Not Found") ? true : false);
		}
		

		if(!$this->page_not_found){
			/* Product Title */
			$this->product_title = strip_tags(trim($this->getInnerHTML($dom, "productTitle")));
			/* Product Title */

			/* Bullet Point */
			$this->bullet_points =  $this->getElementsByClass($dom, "feature-bullets", "span", "a-list-item");
			/* Bullet Point */
			
			/* Product description */
			$this->product_description = strip_tags(trim($this->getInnerHTML($dom, "productDescription")));
			/* Product description */
			
			/* Product Image */
			$this->product_images = $this->getInnerHTML($dom, "imgTagWrapperId");
			/* Product Image */

			/* Customer Review */
			$result_array = array();
			if($this->domain == 'de'){
				$reviews = str_replace(",","",str_replace("Kundenrezensionen","",$this->getInnerHTML($dom, "acrCustomerReviewText")));
			}else{
				$reviews = str_replace(",","",str_replace("customer reviews","",$this->getInnerHTML($dom, "acrCustomerReviewText")));
			}
			if (is_array($reviews) || is_object($reviews)){
				foreach ($reviews as $each_review) {
					  $result_array[] = (int) $each_review;
				}		
			}
			$this->reviews = (count($result_array) > 0 ? max($result_array) : 0);
			/* Customer Review */
			
			/* Bestseller Rank */
			$this->bestseller_rank = $this->getInnerHTML($dom, "zeitgeistBadge_feature_div");
			/* Bestseller Rank */

			/* Prime */
			$this->prime = ($this->queryIsThere($html, "primeUpsellPopover") ? "Yes" : "No");
			/* Prime */
			
			/* Main Category */
			$this->category = $this->getElementsByClass($dom, "nav-subnav","span","nav-a-content");
			/* Main Category */
		}	
	}
	
	public function query($html, $text){
	}
	public function queryIsThere($html, $selector){
		return (strpos($html,$selector)? true: false);			
	}
	
	public function getInnerHTML($dom, $selector){
		$xpath = new \DOMXpath($dom);
		$nodes = $xpath->query("//*[@id='" . $selector . "']");

		$innerHTML = "";
		if(!is_null($nodes)){
			foreach ($nodes as $node) {
				$innerHTML[] = $node->nodeValue;
			}
		}
		if(is_array($innerHTML) & count($innerHTML) == 1){
			$innerHTML = $innerHTML[0];
		}
		return $innerHTML;
		
		/*
		$node = $dom->getElementById($selector);
		if(!is_null($node)){
			$innerHTML= '';
			$children = $node->childNodes;
			foreach ($children as $child)
			{
				$innerHTML .= $child->ownerDocument->saveXML( $child );
			}
			return $innerHTML;
		}else{
			return "";
		}
		*/
	}
	function getElementsByClass($dom, $selector, $tagName, $className) {
		$xpath = new \DOMXpath($dom);
		$nodes = $xpath->query("//*[@id='" . $selector . "']//" . $tagName . "[@class='" . $className . "']");
		
		$result = array();
		if($nodes->length > 0){
			foreach($nodes as $node){
				$result[] = $node->nodeValue;
			}
		}
		return $result;
		
		/*
		$parentNode = $dom->getElementById($selector);
		$nodes=array();

		if(!is_null($parentNode )){
			$childNodeList = $parentNode->getElementsByTagName($tagName);
			for ($i = 0; $i < $childNodeList->length; $i++) {
				$temp = $childNodeList->item($i);
				if (stripos($temp->getAttribute('class'), $className) !== false) {
					$innerHTML= '';
					$children = $temp->childNodes;
					foreach ($children as $child)
					{
						$innerHTML .= $child->ownerDocument->saveXML( $child );
					}
					$nodes[] = $innerHTML;
				}
			}
		}
		
		return $nodes;
		*/
	}	
}
