<?php

class Itremma_Nex_App_Api_Youtube
{
    /**
     * Return youtube id from url
     */
    public static function url2id($url)
    {
        preg_match('/^(https?:\/\/)?(www\.)?youtube\.com[^\s]*v=([a-z_0-9]*)/i', $url, $matches);

        if ( !empty($matches[3]) ) return $matches[3];

        preg_match('/^(https?:\/\/)?(www\.)?youtube\.be\/([a-z_0-9]*)/i', $url, $matches);

        return $matches[3];
    }

    /**
     * Return iframe from id
     */
    public static function id2iframe($id, $attr = [])
    {
        $attr += array(
            'width' => 460,
            'height' => 349,
            'frameborder' => 0,
            'allowfullscree' => 1,
        );

        return '<iframe src="http://www.youtube.com/embed/'.$id.'" '.html::attr($attr).'></iframe>';
    }

    /**
     * Return video image from id
     * @param string id
     * @param int|string which image we would like to get. default | 0 | 1 | 2 | 3
     */
    public static function id2imageUrl($id, $image = 'default')
    {
        return 'http://img.youtube.com/vi/'.$id.'/'.$image.'.jpg' ;
    }
}
