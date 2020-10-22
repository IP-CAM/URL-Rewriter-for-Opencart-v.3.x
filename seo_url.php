<?php
class ControllerStartupSeoUrl extends Controller {
	public function index() {
		// Add rewrite to url class
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}

		// Decode URL
		if (isset($this->request->get['_route_'])) {
			$parts = explode('/', $this->request->get['_route_']);

			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}

			if ($parts[0] == 'product') {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($parts[1]) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);

					if ($url[0] == 'product_id') {
						$this->request->get['product_id'] = $url[1];
					}
				} else {
					$this->request->get['route'] = 'error/not_found';
				}
			} elseif ($parts[0] == 'category') {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($parts[1]) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);

					if ($url[0] == 'category_id') {
						$this->request->get['category_id'] = $url[1];
					}
				} else {
					$this->request->get['route'] = 'error/not_found';
				}
			} elseif ($parts[0] == 'manufacturer') {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($parts[1]) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);

					if ($url[0] == 'manufacturer_id') {
						$this->request->get['manufacturer_id'] = $url[1];
					}
				} else {
					$this->request->get['route'] = 'error/not_found';
				}
			} elseif ($parts[0] == 'information') {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($parts[1]) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);

					if ($url[0] == 'information_id') {
						$this->request->get['information_id'] = $url[1];
					}
				} else {
					$this->request->get['route'] = 'error/not_found';
				}
			} else {
				foreach ($parts as $part) {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

					if ($query->num_rows) {
						$url = explode('=', $query->row['query']);

						if ($url[0] != 'category_id' && $url[0] != 'product_id' && $url[0] != 'manufacturer_id' && $url[0] != 'information_id') {
							$this->request->get['route'] = $query->row['query'];
						}
					} else {
						$this->request->get['route'] = 'error/not_found';

						break;
					}
				}
			}

			if (!isset($this->request->get['route'])) {
				if (isset($this->request->get['product_id'])) {
					$this->request->get['route'] = 'product/product';
				} elseif (isset($this->request->get['category_id'])) {
					$this->request->get['route'] = 'product/category';
				} elseif (isset($this->request->get['manufacturer_id'])) {
					$this->request->get['route'] = 'product/manufacturer/info';
				} elseif (isset($this->request->get['information_id'])) {
					$this->request->get['route'] = 'information/information';
				}
			}
		}
	}

	public function rewrite($link) {
		$url_info = parse_url(str_replace('&amp;', '&', $link));

		$url = '';

		$data = array();

		parse_str($url_info['query'], $data);

		foreach ($data as $key => $value) {
			if (isset($data['route'])) {
				if (($data['route'] == 'product/product' && $key == 'product_id') ||
					($data['route'] == 'product/category' && $key == 'category_id') ||
					(($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') ||
					($data['route'] == 'information/information' && $key == 'information_id')) {
					$_query = $key . '=' . (int)$value;
				} else {
					$_query = $data['route'];
				}

				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($_query) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

				if ($query->num_rows && $query->row['keyword']) {
					if ($data['route'] == 'product/product' && $key == 'product_id') {
						$url .= '/product';
					} elseif ($data['route'] == 'product/category' && $key == 'category_id') {
						$url .= '/category';
					} elseif ($data['route'] == 'product/manufacturer/info' && $key == 'manufacturer_id') {
						$url .= '/manufacturer';
					} elseif ($data['route'] == 'information/information' && $key == 'information_id') {
						$url .= '/information';
					}

					$url .= '/' . $query->row['keyword'];

					unset($data[$key]);
				}
			}
		}

		if ($url) {
			unset($data['route']);

			$query = '';

			if ($data) {
				foreach ($data as $key => $value) {
					$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
				}

				if ($query) {
					$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
				}
			}

			return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
		} else {
			return $link;
		}
	}
}
