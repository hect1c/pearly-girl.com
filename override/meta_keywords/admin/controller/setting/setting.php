<?php
class meta_keywords_ControllerSettingSetting extends ControllerSettingSetting {
	protected function preRender( $templateBuffer ) {
		if ($this->template != 'setting/setting.tpl') {
			return parent::preRender( $templateBuffer );
		}

		// add support for the meta keywords field
		$this->load->language('setting/setting');
		$this->data['entry_meta_keywords'] = $this->language->get('entry_meta_keywords');
		if (isset($this->request->post['config_meta_keywords'])) {
			$this->data['config_meta_keywords'] = $this->request->post['config_meta_keywords'];
		} else {
			$this->data['config_meta_keywords'] = $this->config->get('config_meta_keywords');
		}
		
		// add the meta keywords field to the template file
		$add  = '            <tr>'."\n";
		$add .= '              <td><?php echo $entry_meta_keywords; ?></td>'."\n";
		$add .= '              <td><textarea name="config_meta_keywords" cols="40" rows="5"><?php echo $config_meta_keywords; ?></textarea></td>'."\n";
		$add .= '            </tr>'."\n";
		$this->load->helper( 'modifier' );
		$templateBuffer = Modifier::modifyStringBuffer( $templateBuffer,'<td><textarea name="config_meta_description"',$add,'after',1 );
		return parent::preRender($templateBuffer);
	}
}
?>