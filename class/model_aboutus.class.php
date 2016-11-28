<?php 

class model_aboutus
{
	static function getAboutusInfo(){
		global $_W;
		
		$aboutus = Util::getCache('aboutus','aboutus');
		if(empty($aboutus)){
			$aboutus = Util::getSingleData('zb_task_aboutus',array('uniacid'=>$_W['uniacid']));
			Util::setCache('aboutus','aboutus',$aboutus);
		}
		return $aboutus;
		
	}
	
}


?>