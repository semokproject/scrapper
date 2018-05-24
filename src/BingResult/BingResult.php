<?php

namespace Semok\Scrapper\BingResult;

use SemokLog;
use Exception;
use Semok\Scrapper\BingResult\Exceptions\RequestException;

use FastSimpleHTMLDom\Document;

class BingResult
{
    protected $proxy = false;
    protected $config;
    protected $options;
    protected $filter;
    protected $request_queries = [
        'q' => null,
        'count' => 50,
        'first' => 0,
        'format' => 'rss',
    ];

    public function __construct()
    {
        $this->config = config('semok.scrapper.bingresult');
    }

    public function get($keyword, $options = null, $filter = null)
    {
        $this->keyword = $keyword;
        $this->options = $options;
        $this->filter = $filter;
        $results = $this->getContent();
        if ($this->filter) {
			$items = collect([]);
			$results->get('results')->each(function($item) use($items) {
				try {
                    $result = app()->makeWith($this->filter, ['item' => $item])->handle();
					if ($result) $items->push($result);
				} catch (Exception $e) {
					SemokLog::file('scrapper')->error('BingResultScrapper: Apply Filter: ' . $e->getMessage());
				}
			});
			$results->put('results', $items);
			if (!$results->get('results')->count()) {
				throw new RequestException('Empty Result after filter applied');
			}
		}
        return $results;

    }

    protected function getContent()
    {
        $this->prepareRequest();
        $url = 'http://www.bing.com:80/search?' . http_build_query($this->request_queries);
        try {

            $results = simplexml_load_string(file_get_contents($url));
            $results = json_decode(json_encode($results), 1);
            if (!isset($results['channel']['item'])) {
                throw new RequestException("Invalid bing results format.");
            }
            if (!is_array($results['channel']['item']) || empty($results['channel']['item'])) {
                throw new RequestException("Empty results.");
            }
            $bing = [
                'attributes' => [
                    'url' => $url,
                    'keyword' => $this->keyword,
                    'options' => $this->options,
                    'filter' => $this->filter
                ],
                'results' => $results['channel']['item']
            ];
            return semok_collect($bing);
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

        if (isset($this->options['request_queries']) && is_array($this->options['request_queries'])) {
            $this->request_queries = array_merge($this->request_queries, $this->options['request_queries']);
        }
    }
}
