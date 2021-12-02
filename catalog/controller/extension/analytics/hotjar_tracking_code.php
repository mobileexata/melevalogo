<?php
class ControllerExtensionAnalyticsHotjarTrackingCode extends Controller {
    public function index() {
		return html_entity_decode($this->config->get('analytics_hotjar_tracking_code'), ENT_QUOTES, 'UTF-8');
	}
}
