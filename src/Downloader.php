<?php
/**
 * This is a simple parser class for test task of Hexa company.
 * Created by 19.01.2016 5:27
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2016 Alexander Litvinenko, contact him trustywork@gmail.com
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @version 1.0.0
 */


namespace TrustyWork\ImageDownloader;

use Exception;
use GuzzleHttp\Client as HttpClient;

class Downloader
{
    /*
     * Errors code
     * @todo
     * images url list 100-199
     * save path 200-299
     * options 300-399
     * download and file errors 400-499
     */

    const ERR_EMPTY_IMAGES_LIST = 100;
    const ERR_WRONG_IMAGES_LIST = 101;

    const ERR_EMPTY_SAVE_PATH = 200;
    const ERR_NOT_READABLE_SAVE_PATH = 201;
    const ERR_WRONG_SAVE_PATH = 202;
    const ERR_NOT_WRITABLE_SAVE_PATH = 203;

    const ERR_WRONG_OPTIONS = 300;
    const ERR_WRONG_KEY_OPTIONS = 301;

    const ERR_BAD_LINK_IMAGE = 400;
    const ERR_REFUSED_LINK_IMAGE = 401;
    const ERR_404_LINK_IMAGE = 402;
    const ERR_BAD_FORMAT_IMAGE = 403;
    const ERR_TO_BIG_IMAGE = 404;


    /*
     * @var array
     */
    private $images = [];
    /*
     * @var string
     */
    private $savePath = '';

    /*
     * @var array
     */
    private $options = [];
    /*
     * @var array
     */
    private $ext = ['image/jpeg' => 'jpeg', 'image/png' => 'png', 'image/gif' => 'gif'];

    /*
     * @var array
     */
    private $errors = [];

    /* Set images url list
     * @param mixed $images
     * @return boolean
     */

    public function getOptions()
    {
        return $this->options;
    }


    /* Get images url list
     * @return array
     */

    public function setOptions($options)
    {

        if (!is_array($options)) {
            throw new Exception("Options should be an array", self::ERR_WRONG_OPTIONS);
        }

        if (array_key_exists('max_size',
                $options) && (!is_numeric($options['max_size']) || $options['max_size'] <= 0)
        ) {
            throw new Exception('Key `max_size` must be numeric and greater than zero', self::ERR_WRONG_KEY_OPTIONS);
        }

        $this->options = $options;
        return true;
    }

    /* Set save dir for images
     * @param string $savePath
     * @return boolean
     */

    public function run()
    {
        //checking image and save dir path params of empty
        $images = $this->getImages();
        if( empty( $images)) {
            throw new Exception("Image list should not be empty", self::ERR_EMPTY_IMAGES_LIST);
        }

        $saveDir = $this->getSavePath();
        if( empty( $saveDir)) {
            throw new Exception("Save path should not be empty", self::ERR_EMPTY_SAVE_PATH);
        }

        //download loop
        foreach ($images as $url) {

            //validation url
            $parseUrl = parse_url($url);

            if (empty($parseUrl['scheme']) || empty($parseUrl['host'])) {
                $this->errors[$url] = self::ERR_BAD_LINK_IMAGE;
                return false;
            }

            //Guzzle is use. Сatch his expulsion.
            try {

                //download
                $client = new HttpClient();
                $response = $client->get($url);

                //Checking the size of the file, if necessary
                if ($this->options['max_size']) {

                    $size = $response->getBody()->getSize();
                    if ($this->options['max_size'] < $size) {
                        $this->errors[$url] = self::ERR_TO_BIG_IMAGE;
                        return false;
                    }
                }

                //Checking file mime type. need lnly images
                $mime = $response->getHeader('Content-Type')[0];
                $imageTypes = array_keys($this->ext);
                if (!in_array($mime, $imageTypes)) {
                    $this->errors[$url] = self::ERR_BAD_FORMAT_IMAGE;
                    return false;
                }

                $ext = $this->ext[$mime];

                //save file. Filename is hash his body + ext
                $hash = sha1($response->getBody());
                $name = "{$hash}.{$ext}";
                $path = "{$saveDir}/{$name}";
                if (!file_exists($path)) {

                    file_put_contents($path, $response->getBody());
                }

            } catch (Exception $e) {

                $httpCode = $e->getCode();
                if ($httpCode == 404) {
                    //Image not found error
                    $this->errors[$url] = self::ERR_404_LINK_IMAGE;
                } else {
                    //Common error for other Guzzle error
                    $this->errors[$url] = self::ERR_REFUSED_LINK_IMAGE;
                }
            }
        }

        return !$this->isError();
    }

    /* Get save dir for images
     * @return string
     */

    public function getImages()
    {
        return $this->images;
    }


    /* Set download options
     * @param array $options
     * @return boolean
     * @todo add key validation for new params
     */

    public function setImages($images)
    {
        if (empty($images)) {
            throw new Exception("Image list should not be empty", self::ERR_EMPTY_IMAGES_LIST);
        }

        if (is_string($images)) {
            $images = [$images];
        }

        if (!is_array($images)) {
            throw new Exception("Image list should be array or string", self::ERR_WRONG_IMAGES_LIST);
        }

        $images = array_unique($images);
        $images = array_filter($images, function ($el) {
            return !!$el;
        });
        $images = array_values($images);

        $this->images = $images;
        return true;
    }

    /* Get download options
     * @return array
     */

    public function getSavePath()
    {
        return $this->savePath;
    }


    /* Run download
     * @return boolean
     */

    public function setSavePath($savePath)
    {
        if (empty($savePath)) {
            throw new Exception("Save path should not be empty", self::ERR_EMPTY_SAVE_PATH);
        }

        if (!file_exists($savePath)) {
            throw new Exception("No read permission for the save path or save path not exist",
                self::ERR_NOT_READABLE_SAVE_PATH);
        }

        if (!is_dir($savePath)) {
            throw new Exception("Save path is not a dir", self::ERR_WRONG_SAVE_PATH);
        }

        if (!is_writable($savePath)) {
            throw new Exception("No write permission for the save path", self::ERR_NOT_WRITABLE_SAVE_PATH);
        }

        $this->savePath = $savePath;
        return true;
    }


    /*
     * @return boolean
     */

    public function isError()
    {
        return !empty($this->errors);
    }

    /*
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}