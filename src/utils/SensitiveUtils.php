<?php


namespace Haxibiao\Helpers\utils;


use App\AppConfig;
use Haxibiao\Helpers\utils\Sensitive\HashMap;

class SensitiveUtils
{
    /**
     * 待检测语句长度
     *
     * @var int
     */
    protected $contentLength = 0;

    private static $instance;

    private static $badWords = [];

    /**
     * 敏感词单例
     *
     * @var object|null
     */
    private static $_instance = null;

    /**
     * 敏感词库树
     *
     * @var HashMap|null
     */
    protected $wordTree = null;

    /**
     * 存放待检测语句敏感词
     *
     * @var array|null
     */
    protected static $badWordList = null;

    /**
     * 干扰因子集合
     * @var array
     */
    private $disturbList = array();

    public function interference($disturbList = array())
    {
        $this->disturbList = $disturbList;
        return $this;
    }

    private function __construct()
    {
        $file = $this->file();
        if (file_exists($file)) {
            $text           = file_get_contents($file);
            $badWords       = explode("\n", $text);
            self::$badWords = array_map(function ($text) {
                $text = base64_decode($text);
                $text = str_replace(["\n", "\r", "\n\r", '"', ';'], '', $text);
                return $text;
            }, $badWords);
        }
    }

    public static function file($filePath = null)
    {
        return $filePath ? $filePath : dirname(__FILE__) . '/' . 'bad-word.txt';
    }

    /**
     * 获取单例
     *
     * @return self
     */
    public static function init()
    {
        if (! self::$_instance instanceof self) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * 构建敏感词树【文件模式】
     *
     * @param string $filepath
     *
     * @return $this
     */
    public function setTreeByFile($filepath = '')
    {
        // 词库树初始化
        $this->wordTree = $this->wordTree ?: new HashMap();

        foreach ($this->yieldToReadFile($filepath) as $word) {
            $this->buildWordToTree(trim($word));
        }

        return $this;
    }


    /**
     * 构建敏感词树【数组模式】
     *
     * @param null $sensitiveWords
     *
     * @return $this
     */
    public function setTree($sensitiveWords = null)
    {
        $this->wordTree = new HashMap();

        foreach ($sensitiveWords as $word) {
            $this->buildWordToTree($word);
        }
        return $this;
    }

    /**
     * 检测文字中的敏感词
     *
     * @param string   $content    待检测内容
     * @param int      $matchType  匹配类型 [默认为最小匹配规则]
     * @param int      $wordNum    需要获取的敏感词数量 [默认获取全部]
     * @return array
     */
    public function getBadWord($content, $matchType = 1, $wordNum = 0)
    {
        $this->contentLength = mb_strlen($content, 'utf-8');
        $badWordList = array();
        for ($length = 0; $length < $this->contentLength; $length++) {
            $matchFlag = 0;
            $flag = false;
            $tempMap = $this->wordTree;
            for ($i = $length; $i < $this->contentLength; $i++) {
                $keyChar = mb_substr($content, $i, 1, 'utf-8');

                if ($this->checkDisturb($keyChar)) {
                    $matchFlag++;
                    continue;
                }

                // 获取指定节点树
                $nowMap = $tempMap->get($keyChar);

                // 不存在节点树，直接返回
                if (empty($nowMap)) {
                    break;
                }

                // 存在，则判断是否为最后一个
                $tempMap = $nowMap;

                // 找到相应key，偏移量+1
                $matchFlag++;

                // 如果为最后一个匹配规则,结束循环，返回匹配标识数
                if (false === $nowMap->get('ending')) {
                    continue;
                }

                $flag = true;

                // 最小规则，直接退出
                if (1 === $matchType)  {
                    break;
                }
            }

            if (! $flag) {
                $matchFlag = 0;
            }

            // 找到相应key
            if ($matchFlag <= 0) {
                continue;
            }

            $badWordList[] = mb_substr($content, $length, $matchFlag, 'utf-8');

            // 有返回数量限制
            if ($wordNum > 0 && count($badWordList) == $wordNum) {
                return $badWordList;
            }

            // 需匹配内容标志位往后移
            $length = $length + $matchFlag - 1;
        }
        return $badWordList;
    }

    /**
     * 替换敏感字字符
     *
     * @param        $content      文本内容
     * @param string $replaceChar  替换字符
     * @param bool   $repeat       true=>重复替换为敏感词相同长度的字符
     * @param int    $matchType
     *
     */
    public function replace($content, $replaceChar = '', $repeat = false, $matchType = 1)
    {
        $badWordList = self::$badWordList ? self::$badWordList : $this->getBadWord($content, $matchType);

        // 未检测到敏感词，直接返回
        if (empty($badWordList)) {
            return $content;
        }

        foreach ($badWordList as $badWord) {
            $hasReplacedChar = $replaceChar;
            if ($repeat) {
                $hasReplacedChar = $this->dfaBadWordConversChars($badWord, $replaceChar);
            }
            $content = str_replace($badWord, $hasReplacedChar, $content);
        }
        return $content;
    }

    /**
     * 标记敏感词
     *
     * @param        $content    文本内容
     * @param string $sTag       标签开头，如<mark>
     * @param string $eTag       标签结束，如</mark>
     * @param int    $matchType
     *
     * @return mixed
     */
    public function mark($content, $sTag, $eTag, $matchType = 1)
    {
        $badWordList = self::$badWordList ? self::$badWordList : $this->getBadWord($content, $matchType);

        // 未检测到敏感词，直接返回
        if (empty($badWordList)) {
            return $content;
        }

        foreach ($badWordList as $badWord) {
            $replaceChar = $sTag . $badWord . $eTag;
            $content = str_replace($badWord, $replaceChar, $content);
        }
        return $content;
    }

    /**
     * 被检测内容是否合法
     *
     * @param $content
     *
     * @return bool
     */
    public function islegal($content)
    {
        $this->contentLength = mb_strlen($content, 'utf-8');

        for ($length = 0; $length < $this->contentLength; $length++) {
            $matchFlag = 0;

            $tempMap = $this->wordTree;
            for ($i = $length; $i < $this->contentLength; $i++) {
                $keyChar = mb_substr($content, $i, 1, 'utf-8');
                if ($this->checkDisturb($keyChar)) {
                    $matchFlag++;
                    continue;
                }

                // 获取指定节点树
                $nowMap = $tempMap->get($keyChar);

                // 不存在节点树，直接返回
                if (empty($nowMap)) {
                    break;
                }

                // 找到相应key，偏移量+1
                $tempMap = $nowMap;
                $matchFlag++;

                // 如果为最后一个匹配规则,结束循环，返回匹配标识数
                if (false === $nowMap->get('ending')) {
                    continue;
                }

                return true;
            }

            // 找到相应key
            if ($matchFlag <= 0) {
                continue;
            }

            // 需匹配内容标志位往后移
            $length = $length + $matchFlag - 1;
        }
        return false;
    }

    protected function yieldToReadFile($filepath)
    {
        $fp = fopen($filepath, 'r');
        while (! feof($fp)) {
            yield fgets($fp);
        }
        fclose($fp);
    }

    // 将单个敏感词构建成树结构
    protected function buildWordToTree($word = '')
    {
        if ('' === $word) {
            return;
        }
        $tree = $this->wordTree;

        $wordLength = mb_strlen($word, 'utf-8');
        for ($i = 0; $i < $wordLength; $i++) {
            $keyChar = mb_substr($word, $i, 1, 'utf-8');

            // 获取子节点树结构
            $tempTree = $tree->get($keyChar);

            if ($tempTree) {
                $tree = $tempTree;
            } else {
                // 设置标志位
                $newTree = new HashMap();
                $newTree->put('ending', false);

                // 添加到集合
                $tree->put($keyChar, $newTree);
                $tree = $newTree;
            }

            // 到达最后一个节点
            if ($i == $wordLength - 1) {
                $tree->put('ending', true);
            }
        }

        return;
    }

    /**
     * 敏感词替换为对应长度的字符
     * @param $word
     * @param $char
     *
     * @return string
     */
    protected function dfaBadWordConversChars($word, $char)
    {
        $str = '';
        $length = mb_strlen($word, 'utf-8');
        for ($counter = 0; $counter < $length; ++$counter) {
            $str .= $char;
        }

        return $str;
    }

    /**
     * 干扰因子检测
     * @param $word
     * @return bool
     */
    private function checkDisturb($word)
    {
        return in_array($word, $this->disturbList);
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static;
        }
        return self::$instance;
    }

    /**
     * 补充敏感词
     */
    public static function addWord($text)
    {
        if (self::getInstance()) {
            if (!in_array($text, self::$badWords)) {
                $instance = self::getInstance();
                $file     = $instance->file();
                if (file_exists($file)) {
                    $text = PHP_EOL . base64_encode($text);
                    return file_put_contents($file, $text, FILE_APPEND);
                }
            }
        }
    }

    public static function inWords(array $textArr)
    {
        if (self::getInstance()) {
            foreach ($textArr as $text) {
                if (in_array($text, self::$badWords)) {
                    return true;
                }
            }
        }
    }

    public static function getWords()
    {
        if (self::getInstance()) {
            return self::$badWords;
        }
    }

    /**
     * 敏感词开关
     */
    private function isOpen(){

        $config = AppConfig::where([
            'name'  => 'sensitive',
        ])->first();
        if (empty($config) || $config->state === AppConfig::STATUS_OFF) {
            return false;
        } else {
            return true;
        }
    }
}
