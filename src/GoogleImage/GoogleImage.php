<?php

namespace Semok\Scrapper\GoogleImage;

use SemokLog;
use Storage;
use Exception;
use Semok\Scrapper\GoogleImage\Exceptions\RequestException;

use FastSimpleHTMLDom\Document;

class GoogleImage
{
    protected $proxy = false;
    protected $base_url = 'http://www.google.com';
    protected $config;
    protected $request_queries = [
        'q' => null,
        'tbm' => 'isch',
        'ei' => '',
        'sa' => 'N',
        'safe' => 'off',
        'csl' => '1',
        'gbv' => '2',
        'ijn' => 0,
        'start' => 0,
    ];

    public function __construct()
    {
        $this->config = config('semok.scrapper.googleimage');
    }

    public function get($keyword, $options = array(), $filter = null)
    {
        $this->keyword = $keyword;
        $this->options = $options;
        $this->filter = $filter;
        $images = [];
        $content = $this->getContent();
        $htmldom = new Document;
        $contents = $htmldom->loadHtml($content);
        if (!$contents->find('div.rg_di', 0)) {
            throw new RequestException('Invalid response format');
        }
        $html = $contents->find('div.rg_di');
        foreach ($html as $htmlnya) {
            $image = array();
            if(!$a = $htmlnya->find('div.rg_meta', 0)){
                continue;
            }
            $meta = json_decode($a->innertext);
            if (!isset($meta->ou) || empty(trim($meta->ou))) {
                continue;
            }
            $image['image_url'] = $meta->ou;

            $image['page_url'] = $meta->ru;
            $image['width'] = $meta->ow;
            $image['height'] = $meta->oh;

            $size = $meta->ow.' X '. $meta->oh ;
            $image['size'] = $size;
            $image['thumbnail'] = $meta->tu;

            $explode_title = explode('/',$image['image_url']);
            $explode_title = end($explode_title);
            $explode_title = $this->cleanurl($explode_title);
            $image['image_alt'] = trim($explode_title);

            $image['title'] = $meta->pt;

            if($image['title'] && $image['image_alt']) {
                $image['domain'] = $this->getHost($image['page_url']);
                if ($this->filter) {
                    try {
                        $image = (app()->make($this->filter))->runFilter($image);
                        if ($image) $images[] = $image;
                    } catch (Exception $e) {
                        SemokLog::file('scrapper')->error('GoogleImageScrapper: Apply Filter: ' . $e->getMessage());
                    }
                } else {
                    $images[] = $image;
                }
            }
        }
        if (empty($images)) {
            throw new RequestException("Empty results");

        }
        return $images;
    }

    protected function getContent()
    {
        $this->prepareRequest();
        $requestArgs = [
            'proxy' => $this->proxy
        ];
        $url = $this->base_url . '/search?' . http_build_query($this->request_queries);
        try {

            $content = $this->httpCall($url, '', false, '', true);
            $content = $this->handle302Redirect($content,$this->base_url);
            $content = $this->handleMetaRedirect($content,$this->base_url);
            if (empty($content['response'])) {
                throw new RequestException("Empty response.");
            }
            return $content['response'];
        } catch (Exception $e) {
            throw new RequestException($e->getMessage());
        }
    }

    protected function prepareRequest()
    {
        if (is_array($this->options)) {
            $this->options  = array_merge($this->config['options'], $this->options);
        } else {
            $this->options = $this->config['options'];
        }

        if (isset($this->options['proxy'])) {
            $this->proxy = $this->options['proxy'];
        }
        if (!$this->filter && $this->config['filter']) {
            $this->filter = $this->config['filter'];
        }

        $this->request_queries['q'] = $this->options['keyword_prefix'] . $this->keyword . $this->options['keyword_suffix'];
        $this->request_queries['start'] = ($this->options['page'] - 1) * 100;
        $this->request_queries['ijn'] = $this->request_queries['start'] / 100;

        if (isset($this->options['size'])) {
            switch (strtolower($this->options['size'])) {
                case  'icon':    $this->request_queries['tbs'] = 'isz:i'; break;
                case  'medium':  $this->request_queries['tbs'] = 'isz:m'; break;
                case  'large':   $this->request_queries['tbs'] = 'isz:l'; break;
            }
        }

        if (isset($this->options['request_queries']) && is_array($this->options['request_queries'])) {
            $this->request_queries = array_merge($this->request_queries, $this->options['request_queries']);
        }
    }

    protected function httpCall(
        $url,
        $referer = '',
        $post_call = false,
        $postdata = '',
        $include_header = false,
        $arr_curl_option = array()
    ) {
        $arr_result = array();
        $cookie_file = __FILE__ . "image_grabber.cookie";
        $ch = curl_init();
        if ($include_header !== false) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }

        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_COOKIESESSION, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        if ($post_call !== false) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        }
        if ($this->proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        }
        if (!empty($referer)) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:25.0) Gecko/20100101 Firefox/25.0');
        if (!empty($arr_curl_option)) {
            foreach ($arr_curl_option as $key_option => $value_option) {
                curl_setopt($ch, $key_option, $value_option);
            }
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        $err_no = curl_errno($ch);
        curl_close($ch);
        unset($ch);
        $arr_result['response'] = $response;
        $arr_result['error'] = $error;
        $arr_result['info'] = $info;
        $arr_result['err_no'] = $err_no;
        return $arr_result;
    }

    protected function handle302Redirect($arr_http_call_result = array(), $base_url = '')
    {
        while (
            ($arr_http_call_result['info']['http_code'] === 302) ||
            ($arr_http_call_result['info']['http_code'] === 301)
        ) {
            list($header, $body) = explode("\r\n\r\n", $arr_http_call_result['response'], 2);
            $meta_redirection_url = '';
            $find_me = 'Location: ';
            $pos = stripos($header, $find_me);
            if ($pos !== false) {
                $meta_redirection_url = trim(substr($header, $pos+strlen($find_me)));
                $find_me2 = "\r\n";
                $pos2 = strpos($meta_redirection_url, $find_me2);
                if ($pos2 !== false)
                $meta_redirection_url = trim(substr($meta_redirection_url, 0, $pos2));
            } else {
                $pos = stripos($arr_http_call_result['response'], $find_me);
                if ($pos !== false) {
                    $meta_redirection_url = trim(substr($arr_http_call_result['response'], $pos + strlen($find_me)));
                    $find_me2 = "\r\n";
                    $pos2 = strpos($meta_redirection_url, $find_me2);
                    if ($pos2 !== false) $meta_redirection_url = trim(substr($meta_redirection_url, 0, $pos2));
                }
            }
            if (!empty($meta_redirection_url) && !empty($base_url)) {
                $find_me = $base_url;
                $pos = strpos($meta_redirection_url, $find_me);
                if (($pos === false) && ($meta_redirection_url[0] === '/')) {
                    $meta_redirection_url=$base_url.$meta_redirection_url;
                }
            }
            if (!empty($url)) $referer = $url;
            else $referer = '';
            $url = $meta_redirection_url;
            $arr_http_call_result = $this->httpCall($url, $referer, false, '', true);
        }
        return $arr_http_call_result;
    }

    protected function handleMetaRedirect($arr_http_call_result = array(), $base_url = '')
    {
        $pos = false;
        do {
            $html = new Document;
            $html->loadHtml($arr_http_call_result['response']);
            $noscript_meta = $html->find('noscript meta', 0);
            if (!empty($noscript_meta)) {
                $str_meta_redirection = trim(htmlspecialchars_decode($noscript_meta->content));
                $find_me = '0;url=';
                $pos = strpos($str_meta_redirection, $find_me);
                if ($pos !== false) {
                    $meta_redirection_url=trim(substr($str_meta_redirection, $pos + strlen($find_me)));
                    if (!empty($base_url)) {
                        $find_me = $base_url;
                        $pos = strpos($meta_redirection_url, $find_me);
                        if (($pos===FALSE) && ($meta_redirection_url[0]==='/')) {
                            $meta_redirection_url = $base_url . $meta_redirection_url;
                        }
                    }
                    if (!empty($url)) $referer = $url;
                    else $referer = '';
                    $url = $meta_redirection_url;
                    $arr_http_call_result = $this->httpCall($url, $referer);
                }
            }
            $html->clear();
            unset($html);
        }
        while($pos !== false);
        return $arr_http_call_result;
    }

    protected function getHost($Address)
    {
        if (empty($Address)) return '';
        $parseUrl = parse_url(trim($Address));
        return trim($parseUrl['host'] ? $parseUrl['host'] : array_shift(explode('/', $parseUrl['path'], 2)));
    }

    protected function cleanurl($string)
    {
        $string = str_replace(" ", "-", trim($string));
        $string = preg_replace('/[^A-Za-z0-9\-\.]/', '', $string);
        return preg_replace('/-+/', ' ', $string);
    }
}
