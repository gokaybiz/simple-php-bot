<?php

class Bot {
    private $dir, $cat, $site, $client, $jar, $products = [];

    function __construct($opts = null) {
        if ($opts == null) throw new Exception('You have to set config.');

        if (empty($opts['category'])) throw new Exception('You have to choose a category.');
        $this->cat = $opts['category'];
        
        if (empty($opts['save_folder'])) $this->dir = 'bot-img';
        else $this->dir = $opts['save_folder'];
        
        if (!file_exists(__DIR__ . '/' . $this->dir)) mkdir(__DIR__ . '/' . $this->dir, 0777, true);

        $this->site = 'https://www.a*****************.com/';
        $this->client = $this->initClient();
        $this->startSession();
        $this->setListingFilterToAll();
    }
    private function initClient() {
        if ($this->client == null) {
            $this->jar = new Requests_Cookie_Jar([]);
            $cl = new Requests_Session($this->site);
            $cl->headers = [
                'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36',
                'referer' => $this->site . $this->cat
            ];
            $cl->options = ['cookies' => $this->jar];
            return $cl;
        }
        throw new Exception('Already initialized!');
    }
    private function startSession() {
        return $this->client->get($this->cat);
    }
    private function setListingFilterToAll() {
        return $this->client->post('processnaked.php?p=categoryfilter_output&ajax=true', ['x-requested-with' => 'XMLHttpRequest'], 'parameter=countlist__9999');
    }
    public function crawl($between = null) {
        if($between != null && is_array($between))
            $this->crawlCategory($between);
        else $this->crawlCategory();

        $this->crawlProduct();
        return $this;
    }
    public function get($i = null) {
        if (count($this->products) == 0) throw new Exception('There is no product.');
        
        return $i == null ? $this->products : $this->products[$i];
    }
    public function download() {
        if (count($this->products) == 0) throw new Exception('There is no product.');

        foreach ($this->products as $product) {
            foreach ($product['img'] as $thumb) {
                $thumbName = explode('/', $thumb);
                $this->client->get($thumb, [], ['filename' => dirname( __FILE__ ) . '/' . $this->dir . '/' . array_pop($thumbName)]);
            }
        }
        return true;
    }
    private function crawlCategory($sub = null) {
        $dom = $this->domParser($this->cat, '.listingProductListing');

        $productList = $dom->find('.listingProductListing');
        
        $start = 0;
        $finish = null;
        if ($sub != null) {
            $start = $sub[0]-1;
            if (isset($sub[1]) && ($sub[1] > $sub[0])) {
                $finish = $sub[1]-1;
            }
        }

        foreach ($productList as $i => $product) {
            if ($i < $start)
                continue;
            $pr = [];
            $pr['name'] = $product->find('.list_title_type1_text a span')->text();
            $pr['price'] = $product->find('.divdiscountprice span')->text();
            $pr['price_undiscounted'] = $product->find('.listingPriceMarket span')->text() ?? null;
            $pr['publisher'] = $product->find('.listingBrand span')->text();
            $pr['publisher_img'] = null;
            $pr['img'] = [];
            $pr['info'] = null;
            $pr['linkForMore'] = $product->find('.list_title_type1_text a')->attr('href');
            array_push($this->products, $pr);
            if ($finish != null)
                if ($i == $finish)
                    break;
        }
        return $this->products;
    }
    private function crawlProduct() {
        return $this->products = array_map(function ($product){
            $dom = $this->domParser($product['linkForMore'], '.container');
            
            $product['publisher_img'] = $dom->find('.marka a span img')->attr('src');
            $product['info'] = preg_replace('/<div class="tab_icerik">(.*?)<\/div>/s', '$1', $dom->find('.tab_icerikWrp .tab_icerik')->first()->html());
            
            $imgs = $dom->find('.detailThumbnails ul li')->has('[style]');
            foreach ($imgs as $img) {
                array_push($product['img'], $this->site . $img->find('a')->attr('href'));
            }
            return $product;
        }, $this->products);
    }
    private function domParser($cl, $ctx) {
        return htmlqp($this->client->get($cl)->body, $ctx, ['convert_to_encoding' => 'UTF-8']);
    }
}

?>
