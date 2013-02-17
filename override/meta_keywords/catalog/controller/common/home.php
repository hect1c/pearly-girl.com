<?php  
class meta_keywords_ControllerCommonHome extends ControllerCommonHome {
	public function index() {
		$this->document->setKeywords($this->config->get('config_meta_keywords'));
		parent::index();
	}
}
?>