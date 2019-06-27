<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

include('simple_html_dom.php');

class ScannerController extends Controller
{
    private $url;
    private $status_code;
    private $gzip;
    private $http2;
    private $robots;
    private $alts;
    private $pagespeed;
    private function _set_status_code()
    {
        $handle = curl_init($this->url);
        curl_setopt($handle,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        return $httpCode;
    }

    private function _set_gzip()
    {
        $data = array('headurl' => $this->url);
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context  = stream_context_create($options);
        $result = file_get_contents("https://smallseotools.com/check-gzip-compression/", false, $context);
        if ($result === FALSE)
        {
            $this->gzip = "error";
        }
        if(strpos($result, "Wow it's gzip enabled") !== false)
            $this->gzip = "supported";
        else
            $this->gzip = "not supported";
    }

    private function _set_http2()
    {
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL            => $this->url,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
        ]);
        curl_setopt($handle,CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($handle);
        if ($response !== false && strpos($response, "HTTP/2") === 0)
        {
            $this->http2 = "supported";
        }
        elseif ($response !== false) {
            $this->http2 = "not supported";
        }
        else
        {
            $this->http2 = "error";
        }
        curl_close($handle);
    }

    private function _set_robots()
    {
        $robotsUrl = $this->url . "/robots.txt";
        $handle = curl_init();
        $flag = true;
        curl_setopt($handle, CURLOPT_URL, $robotsUrl);
        curl_setopt($handle, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle,CURLOPT_SSL_VERIFYPEER, false);
        $file = curl_exec($handle);
        $file = str_replace(' ', '', $file);
        foreach(explode("\n", $file ) as $line)
        {
            if(strpos($line, "Disallow") !== false)
            {
                $tmp = explode(":", $line);
                $string = preg_replace('/\s+/', '', $tmp[1]);
                if($string != '')
                {
                    $this->robots = "at least 1 is denied";
                    $flag = false;
                    break;
                }
            }
        }
        curl_close($handle);

        try
        {
            $tags = get_meta_tags($this->url);
        }
        catch (\Exception $e)
        {
            $this->robots = $e;
            return;
        }
        if(isset($tags["robots"]))
        {
            //dd(var_dump($tags["robots"]));
            if(strpos($tags["robots"], "noindex") !== false)
            {
                $this->robots = "at least 1 is denied";
                $flag = false;
            }
        }

        if($flag)
            $this->robots = "supported";
    }

    private function _set_alts()
    {
        $flag = true;
        try
        {
            $html = file_get_html($this->url);
        }
        catch (\Exception $e)
        {
            $this->alts = $e;
            return;
        }

        foreach($html->find('img') as $element)
        {
            if($element->alt == '')
            {
                $this->alts = "at least 1 is missing";
                $flag = false;
                break;
            }
        }
        if ($flag)
        {
            $this->alts = "all are set";
        }
    }

    private function _set_page_speed()
    {
        $url = $this->url;
        $key = 'AIzaSyBiRwTG3GXuTSYFn5u6Ygq3hmEDWSNEd4I';
        // View https://developers.google.com/speed/docs/insights/v1/getting_started#before_starting to get a key
        try
        {
            $this->pagespeed = json_decode(file_get_contents("https://www.googleapis.com/pagespeedonline/v1/runPagespeed?url=$url&key=$key"));
        }
        catch (\Exception $e)
        {
            $this->pagespeed = array("Error");
            return;
        }
    }

    public function __call__()
    {
        $url = request()->validate([
            'url' => 'required'
        ]);
        $this->url = $url['url'];
        $this->status_code = $this->_set_status_code();

        if($this->status_code == 0)
        {
            return back()->withErrors(['Invalid URL --> status code 0']);
        }

        $this->_set_gzip();
        $this->_set_http2();
        $this->_set_robots();
        $this->_set_alts();
        $this->_set_page_speed();

        $data = [
            "status" => $this->status_code,
            "gzip" => $this->gzip,
            "http2" => $this->http2,
            "robots" => $this->robots,
            "alts" => $this->alts,
            "pagespeed" => $this->pagespeed,
            "url" => $this->url
        ];

        return view('show', compact('data'));
    }

}
