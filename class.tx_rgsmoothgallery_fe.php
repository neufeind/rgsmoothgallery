<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Georg Ringer <typo3@ringerge.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Hook for the 'rgsmoothgallery' extension.
 *
 * @author	Georg Ringer <http://www.rggooglemap.com/>
 */

class tx_rgsmoothgallery_fe{

  // hook for tt_news
	function extraItemMarkerProcessor($markerArray, $row, $lConf, &$pObj) {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj'); // local cObj.	
		$this->pObj = &$pObj;
		$this->realConf = $pObj;
		
		// configuration array of rgSmoothGallery
    $rgsgConfDefault = $this->realConf->conf['rgsmoothgallery.'];
    
    // merge with special configuration (based on chosen CODE [SINGLE, LIST, LATEST]) if this is available
    if (is_array($rgsgConfDefault[$pObj->config['code'].'.'])) {
			$rgsgConf = t3lib_div::array_merge_recursive_overrule($rgsgConfDefault, $rgsgConfDefault[$pObj->config['code'].'.']);
		} else {
    	$rgsgConf = $rgsgConfDefault;
		}
		
		#echo t3lib_div::view_array($rgsgConf);
		$this->rgsgConf = $rgsgConf;
		// if the configuration is available, otherwise just do nothing
    if ($rgsgConf) {

      // unique ID > uid of the record
      $uniqueId = $markerArray['###NEWS_UID###'];

      // query for the images & caption
      $field = 'pid,uid,imagecaption,image';
      $table = 'tt_news';
      $where = 'uid = '.$uniqueId;   
      $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($field,$table,$where);
      $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
      if ($GLOBALS['TSFE']->sys_language_content) {
  			$OLmode = ($this->sys_language_mode == 'strict'?'hideNonTranslated':'');
  			$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tt_news', $row, $GLOBALS['TSFE']->sys_language_content, '');
  		}

      // needed fields: image & imagecaption
      $images = explode(',',$row['image']);
    	$caption = explode("\n",$row['imagecaption']);
    		
      // If there are any images and minimum count of images is reached
      if ($row['image'] && count($images) >= $rgsgConf['minimumImages']) {
    	  // call rgsmoothgallery
    	  require_once( t3lib_extMgm::extPath('rgsmoothgallery').'pi1/class.tx_rgsmoothgallery_pi1.php'); 
				$this->gallery = t3lib_div::makeInstance('tx_rgsmoothgallery_pi1');
    	  
    	  // if no js is available
    	  $noJsImg =   $rgsgConf['big.'];
        $noJsImg['file'] = 'uploads/pics/'.$images[0];   

        if ($rgsgConf['externalControl']==1) {
          $externalControl1 = 'var myGallery'.$uniqueId.';';
        } else {
          $externalControl2 = 'var';  
        }
        
        // real unique key, needed for more than 1 view of tt_news on 1 page
        $uniqueId = $this->realConf->config['code'].$uniqueId;    
      
        // configuration of gallery
        $lightbox = ($rgsgConf['lightbox']==1) ? 'true' : 'false';
        $lightbox2= ($rgsgConf['lightbox']==1) ? 'var mylightbox = new Lightbox();' : '';
        $duration = ($rgsgConf['duration']) ? 'timed:true,delay: '.$rgsgConf['duration'] : 'timed:false';
        $thumbs   = ($rgsgConf['showThumbs']==1) ? 'true' : 'false';
        $arrows   = ($rgsgConf['arrows']==1) ? 'true' : 'false';
        
        // advanced settings (from TS + tab flexform configuration)
        $advancedSettings.=  ($rgsgConf['hideInfoPane']==1) ? 'showInfopane: false,' : ''; 
        if ($rgsgConf['thumbOpacity'] && $rgsgConf['thumbOpacity'] > 0 && $rgsgConf['thumbOpacity']<=1) $advancedSettings.= 'thumbOpacity: '.$rgsgConf['thumbOpacity'].',';
        if ($rgsgConf['slideInfoZoneOpacity'] && $rgsgConf['slideInfoZoneOpacity'] && $rgsgConf['slideInfoZoneOpacity'] > 0 && $rgsgConf['slideInfoZoneOpacity']<=1) $advancedSettings.= 'slideInfoZoneOpacity: '.$rgsgConf['slideInfoZoneOpacity'].',';   
        $advancedSettings.=  ($rgsgConf['thumbSpacing']) ? 'thumbSpacing: '.$rgsgConf['thumbSpacing'].',' : ''; 
    
        // external thumbs
        $advancedSettings.= ($rgsgConf['externalThumbs']) ? 'useExternalCarousel:true,carouselElement:$("'.$rgsgConf['externalThumbs'].'"),' : '';
  
              
    	  // configuration
    	  $configuration = '		
  
    		<script type="text/javascript">'.$externalControl1.'
    			function startGallery'.$uniqueId.'() {
    			  if(window.gallery'.$uniqueId.')
    			    {
    			    try
    			      {
    				    '.$externalControl2.' myGallery'.$uniqueId.' = new gallery($(\'myGallery'.$uniqueId.'\'), {
    					    
    					    '.$duration.',
    					      showArrows: '.$arrows.',
                  showCarousel: '.$thumbs.',
                  embedLinks:'.$lightbox.',
                  '.$advancedSettings.'
    					    lightbox:true    					    
    				    });
    				    var mylightbox = new Lightbox();
    				    }catch(error){
    				    window.setTimeout("startGallery'.$uniqueId.'();",2500);
    				    }
    				  }else{
    				  window.gallery'.$uniqueId.'=true;
    				  if(this.ie)
    				    {
    				    window.setTimeout("startGallery'.$uniqueId.'();",3000);
    				    }else{
    				    window.setTimeout("startGallery'.$uniqueId.'();",100);
    				    }
    				  }
    			}
    			window.onDomReady(startGallery'.$uniqueId.');
    		</script>
      <noscript>
  		  <div><img src="'.$this->cObj->IMG_RESOURCE($noJsImg).'"  /></div>
  		</noscript>
        ';       
         
        // get the JS

      
        $content =$this->gallery->getJs(1,1,1,0,$rgsgConf['width'],$rgsgConf['height'],$rgsgConf['width'],$rgsgConf['height'],'',$uniqueId,$rgsgConf,$configuration);
        // Begin the gallery
        $content.=  $this->gallery->beginGallery($uniqueId);
        // add the images
        $i=0;
        foreach ($images as $key=>$value) {
          $path = 'uploads/pics/'.$value;
          // single Image
          $imgTSConfigThumb = $rgsgConf['thumb.'];
          $imgTSConfigThumb['file'] = $path;
          $imgTSConfigBig =   $rgsgConf['big.'];
          $imgTSConfigBig['file'] = $path;        
          $imgTSConfigLightbox = $rgsgConf['lightbox.'];
          $imgTSConfigLightbox['file'] = $path;        
         # $lightbox = ($rgsgConf['lightbox']==1) ? $this->cObj->IMG_RESOURCE($imgTSConfigLightbox) : $this->cObj->IMG_RESOURCE($imgTSConfigLightbox);
         
          // caption text
          $text =explode('|',$caption[$i]);
          
          // add image

                  $content.=$this->addImage(
			    $path,
          $text[0], 
            $text[1],
          true,
          true,
          $path,
          $limitImages
        );
    			$i++;
        } # end foreach file
        
        // end of image    	 
        $content.=$this->gallery->endGallery(); 
     
        // write new gallery into the marker    
        $markerArray['###NEWS_IMAGE###'] ='<div class="news-single-img">'.$content.'</div>';
      } # end if ($row['image']..
    } # end if ($rgsgConf) {

		return $markerArray;
	} #end extraItemMarkerProcessor
	
	function addImage($path,$title,$description,$thumb,$lightbox,$uniqueID,$limitImages=0) {
    if ($this->rgsgConf['hideInfoPane']!=1) {
      $text = (!$title) ? '' : "<h3>$title</h3>";
      $text.=(!$description) ? '' : "<p>$description</p>";
    }

    //  generate images
    if ($this->rgsgConf['watermark']) {
      $imgTSConfigBig = $this->rgsgConf['big2.'];
      $imgTSConfigBig['file.']['10.']['file'] = $path;
      $imgTSConfigLightbox = $this->rgsgConf['lightbox2.'];
      $imgTSConfigLightbox['file.']['10.']['file'] = $path; 
    } else {
      $imgTSConfigBig = $this->rgsgConf['big.'];
      $imgTSConfigBig['file'] = $path;
      $imgTSConfigLightbox = $this->rgsgConf['lightbox.'];
      $imgTSConfigLightbox['file'] = $path;               
    }  
    $bigImage = $this->cObj->IMG_RESOURCE($imgTSConfigBig);

    $lightbox =  ($this->rgsgConf['lightbox']) ? $this->cObj->IMG_RESOURCE($imgTSConfigLightbox) : 'javascript:void(0)' ;
  	$lightBoxImage='<a href="'.$lightbox.'" title="Open Image" class="open"></a>';

    if ($this->rgsgConf['showThumbs']) {
      $imgTSConfigThumb = $this->rgsgConf['thumb.'];
      $imgTSConfigThumb['file'] = $path;     
      $thumbImage = '<img src="'.$this->cObj->IMG_RESOURCE($imgTSConfigThumb).'" class="thumbnail" />';
    }

    // build the image element    
    $singleImage .= '
      <div class="imageElement">
        '.$text.$lightBoxImage.'
        <img src="'.$bigImage.'" class="full" />
        '.$thumbImage.'
      </div>';      
    return $singleImage;
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rgsmoothgallery/class.tx_rgsmoothgallery_fe.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rgsmoothgallery/class.tx_rgsmoothgallery_fe.php']);
}

?>