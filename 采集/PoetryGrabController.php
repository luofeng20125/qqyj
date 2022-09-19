<?php

namespace Modules\Poetry\Http\Controllers;

use App\Exceptions\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Poetry\Entities\PoetryTagContent;
use Modules\Poetry\Services\PoetryService;
use Modules\Poetry\Entities\PoetryContent;
use Modules\Poetry\Entities\PoetryDifficulty;
use Modules\Poetry\Entities\PoetryGenre;
use Modules\Poetry\Entities\PoetryTag;
use Modules\Poetry\Http\Requests\PoetryContentRequest;

class PoetryGrabController extends Controller
{
    protected $poetryService;

    private $is_request_audio = 0;//请求语音开关 1开 0关

    public function __construct(PoetryService $poetryService)
    {
        $this->poetryService = $poetryService;
    }

    public function index()
    {
        echo "为防止数据出错此项应由专人操作";
        die;//精选完成5 下次从6开始

//        $list_url ="https://xuegushi.cn/gushi/tangshi"; // 列表url
        $list_url = "https://xuegushi.cn/gushi/songci"; // 列表url

        //以下几项是需要配置的
        $this->is_request_audio = 1;//请求语音开关 1开 0关

        //tags 1唐诗 2宋词 3唐诗三百首 4 课本教学 5 近现代 6 其他朝代诗 7 其他朝代词 8 暂未分类
        $tags = ['2'];//标签  手动设置

        //-------------------change s
        //页面中的排序 第几个写几 从1开始
        $px = 1;

        //content表里的tag用于采集时临时记录分类 如"一年级上册"
        $category = ["宋词精选"];
        //-------------------change e
        //difficulty 1简单 2一般 3较难 4 困难 5 暂未区分
        $difficulty = 5;//难度 手动设置

        //genre  1五言绝句 2七言绝句 3五言律诗 4七言律诗 5五言古诗 6七言古诗 7乐府诗 8 词 9 其他 10暂未区分
        $genre = 10;//体裁


        $htmlstr = $this->myTrim($this->getHtml($list_url));

        preg_match('/<divclass="gushiContentclearfix">[\s\S]*?<divclass="main_rightcol-md-4">/i', $htmlstr,
            $matches);
        $textcontent = $matches[0];
        preg_match_all('/<ahref="(.*?)"title="/', $textcontent, $matches);
        $songcijingxuan_urls= $matches[1];
//        dd($songcijingxuan_urls);
        // urls
        preg_match_all('/<divclass="book-itemclearfix">(.*?)<\/div><\/div>/', $textcontent, $matches);

//        $gs =[];
//        foreach ($matches[0] as $k=>$v){
//            preg_match_all('/<ahref="(.*?)"title="/', $v, $matches);
//            if($k ==($px-1)){
//                $gs = $matches[1];
//            }
//        }

//        foreach ($gs as $k => $v) {
//
//                $this->getone($v, $tags, $difficulty, $genre, $category);
//        }

        $gs = $songcijingxuan_urls;//根据这个不一样页面

//        static $page=1;
//        while ($page<10){
//            echo $page;
//            sleep(5);
//
//            $page++;
//        }
//
//
//        die;
        $page =5;//修改页码 完成5

        $every=10;
        $start =1+$every*($page-1);
        $end=$every*$page;
        foreach ($gs as $k => $v) {
            if ($k>=$start-1 && $k<=$end-1) {
                $this->getone($v, $tags, $difficulty, $genre, $category);
            }
        }


        // $this->getone("https://xuegushi.cn/poem/70358", $tags, $difficulty, $genre, $category);
        die;

        return view('poetry::poetry_grab.index', compact('data'));
    }

    public function getone($url = "", $tags = ["8"], $difficulty = 5, $genre = 10, $category)
    {
//        $url = "https://xuegushi.cn/poem/70027";
        $htmlcontentstr = $this->myTrim($this->getHtml($url));
        //标题
        preg_match('/<aclass="title-link"href="(.*?)"target="_blank">(.*?)<\/a>/i', $htmlcontentstr, $matches);
        $title = str_replace("&middot;", "·", $matches[2]);


        //朝代
        preg_match('/<aclass="author_dynasty"href="(.*?)"target="_blank">(.*?)<\/a>/i', $htmlcontentstr, $matches);
        $dynasty = $matches[2];
        if (mb_substr($dynasty, mb_strlen($dynasty) - 1,
                mb_strlen($dynasty)) == "代" && $dynasty != "五代" && $dynasty != "近代" && $dynasty != "现代" && $dynasty != "近现代") {
            $dynasty = mb_substr($dynasty, 0, mb_strlen($dynasty) - 1);
        }
        //作者
        preg_match('/<aclass="author_name"href="(.*?)"target="_blank">(.*?)<\/a>/i', $htmlcontentstr, $matches);
        $author = isset($matches[2]) ? $matches[2] : "";
        if (!$author) {
            //<a class="author_name" target="_blank">
            preg_match('/<aclass="author_name"target="_blank">(.*?)<\/a>/i', $htmlcontentstr, $matches);
            $author = isset($matches[1]) ? $matches[1] : "佚名";
        }

//        $htmlcontentstr=<<<str
//<div class="poem-content"><pclass="poem-xu">丙辰中秋，欢饮达旦，大醉，作此篇，兼怀子由。</p><pclass="poem-xu">丙辰中222秋，欢饮达旦，大醉，作此篇，兼怀子由。</p><p class="p-content">明月几时有？把酒问青天。不知天上宫阙，今夕是何年。我欲乘风归去，又恐琼楼玉宇，高处不胜寒。起舞弄清影，何似在人间？</p><p class="p-content">转朱阁，低绮户，照无眠。不应有恨，何事长向别时圆？人有悲欢离合，月有阴晴圆缺，此事古难全。但愿人长久，千里共婵娟。</p>
//                                                     </div>
//str;


        //序
        preg_match_all('/<pclass="poem-xu">([\s\S]*?)<\/p>/i', $htmlcontentstr, $matches);

        $xu = isset($matches[1]) ? $matches[1] : [];

        //标签
//        $htmlcontentstr=<<<str
//<div class="poem-tag">
//                        <p>
//                                                                                                <a href="https://xuegushi.cn/poem?tag=宋词三百首" class="tag" target="_blank">宋词三百首 ,</a>                                                                    <a href="https://xuegushi.cn/poem?tag=宋词精选" class="tag" target="_blank">宋词精选 ,</a>                                                                    <a href="https://xuegushi.cn/poem?tag=初中古诗" class="tag" target="_blank">初中古诗 ,</a>                                                                    <a href="https://xuegushi.cn/poem?tag=高中古诗" class="tag" target="_blank">高中古诗 ,</a>                                                                    <a href="https://xuegushi.cn/poem?tag=豪放" class="tag" target="_blank">豪放 ,</a>                                                                    <a href="https://xuegushi.cn/poem?tag=中秋节" class="tag" target="_blank">中秋节 ,</a>                                                                    <a href="https://xuegushi.cn/poem?tag=月亮" class="tag" target="_blank">月亮 ,</a>                                                                    <a href="https://xuegushi.cn/poem?tag=怀人" class="tag" target="_blank">怀人 ,</a>                                                                    <a href="https://xuegushi.cn/poem?tag=祝福" class="tag" target="_blank">祝福</a>                                                                                    </p>
//                    </div>
//str;
//        $htmlcontentstr = $this->myTrim($htmlcontentstr);

        preg_match_all('/<ahref="https:\/\/xuegushi.cn\/poem\?tag=([\s\S]*?)"class="tag"target="_blank">/i',
            $htmlcontentstr, $matches);

        $truetag = $matches[1];


        //判断是否已经存在
        $is_exist = PoetryContent::where([
            'title' => trim($title), 'author' => trim($author), 'dynasty' => trim($dynasty),
        ])->first();
        if ($is_exist) {

            $flag = '';
            //内容存在，tag不存在的插入
            foreach ($tags as $v) {
                $tag_is_exist = PoetryTagContent::where([
                    'poetry_tag_id' => $v, 'poetry_content_id' => $is_exist->id,
                ])->first();
                if (!$tag_is_exist) {
                    $poetry_tag_content = new PoetryTagContent();
                    $tagcontentdata = [
                        'poetry_tag_id' => $v,
                        'poetry_content_id' => $is_exist->id,
                    ];
                    $poetry_tag_content->fill($tagcontentdata);
                    $poetry_tag_content->save();
                    $flag = '-已插入标签';
                }

            }

            //category保留
            $t = json_decode($is_exist->tag);
            if (isset($t->category)) {
                if (count($category) > 0) {
                    foreach ($category as $v) {
                        if (!in_array($v, $t->category)) {
                            array_push($t->category, $v);
                        }
                    }
                }

            } else {
                if ($t) {
                    array_push($category, $t);
                }//这里是修正之前的格式
                $t = [
                    'category' => $category,
                    'xu' => $xu,
                    'truetag' => $truetag,
                ];
            }
            $is_exist->tag = json_encode($t);
            $is_exist->save();


            echo $title."-内容已经存在".$flag;
            echo "<hr>";
            return true;
        }


        //内容
        preg_match('/<divclass="poem-content">([\s\S]*?)<\/div>/i', $htmlcontentstr, $matches);
        //dd($this->myTrim(strip_tags($matches[1])));//不用这里，这里是获取所有内容
        preg_match_all('/<pclass="p-content">([\s\S]*?)<\/p>/i', $matches[0], $matches);
        $content = $matches[1];

        //注释、翻译、赏析

        preg_match('/<divclass="main_leftcol-md-8">([\s\S]*?)<divclass="main_rightcol-md-4">/i', $htmlcontentstr,
            $matches);
        $cont = $this->myTrim($matches[0]);

        preg_match_all('/<divclass="poem-card">([\s\S]*?)<\/div><\/div>/i', $cont, $matches);

        $all = $matches[0];
        $translation = []; //译文
        $notes = [];//注释
        $appreciation = [];//赏析

        if ($all) {
            for ($i = 0; $i < count($all); $i++) {

                preg_match('/<h2class="title">([\s\S]*?)<\/h2>/i', $all[$i], $matches);
                $flag = isset($matches[1]) ? $matches[1] : '';
                preg_match_all('/<pclass="p-content[\s\S]*?>([\s\S]*?)<\/p>/i', $all[$i], $matches);
                if ($flag == '翻译') {
                    preg_match_all('/<pclass="p-content[\s\S]*?>([\s\S]*?)<\/p>/i', $all[$i], $matches);
                    $translation = $matches[1];
                }
                if ($flag == '注释') {
                    preg_match_all('/<liclass="p-content[\s\S]*?>([\s\S]*?)<\/li>/i', $all[$i], $matches);//这里跟其他的不一样，注意
                    $notes = $matches[1];
                }
                if ($flag == '赏析') {
                    preg_match_all('/<pclass="p-content[\s\S]*?>([\s\S]*?)<\/p>/i', $all[$i], $matches);
                    $appreciation = $matches[1];
                }
            }
        }

        //正文括号内容处理
        $content = $this->deleteBrackets($content);

        //加p处理
        $content = $this->deltext($content);
        $translation = $this->deltext($translation);
        $notes = $this->deltext($notes);
        $appreciation = $this->deltext($appreciation);

        //转音频内容处理
        $audiocontent = "<p>{$title}。</p><p>{$dynasty}。</p><p>{$author}。</p>{$content}";

        $data = [
            "title" => $title,
            "dynasty" => $dynasty,
            "author" => $author,
            "tags" => null,
            "difficulty" => $difficulty,
            "genre" => $genre,
            "iscommend" => "2",
            "isshow" => "1",
            "audiourl" => null,
            "combination" => null,
            "isaudit" => "2",
            "click" => null,
            "content" => $content,
            "translation" => $translation,
            "notes" => $notes,
            "appreciation" => $appreciation,
            "background" => null,
            "audiocontent" => $audiocontent,
            "origintype" => 1,
        ];

        $poetry_content = new PoetryContent();
        $poetry_content->fill($data);
        //转音频、拼音
        $this->deal($data, $poetry_content);
        //临时记录分类 这个字段暂时没有用到
        if ($category || $xu || $truetag) {
            $t = [
                'category' => $category,
                'xu' => $xu,
                'truetag' => $truetag,

            ];
            $poetry_content->tag = json_encode($t);
        }
        $poetry_content->save();

        //插入标签关联表
        foreach ($tags as $v) {
            $poetry_tag_content = new PoetryTagContent();
            $tagcontentdata = [
                'poetry_tag_id' => $v,
                'poetry_content_id' => $poetry_content->id,
            ];
            $poetry_tag_content->fill($tagcontentdata);
            $poetry_tag_content->save();
        }


        echo $title."<span style='color: green'>-插入成功</span>";
        echo "<hr>";

    }

    /**
     * 删除正文中的括号，一般里面是通假字说明；
     * @param $arr
     */
    private function deleteBrackets($arr)
    {
        $arr = array_map(function ($v) {
            return preg_replace("/\（.+\）/", "", $v);
        }, $arr);
        $arr = array_map(function ($v) {
            return preg_replace("/\(.+\)/", "", $v);
        }, $arr);

        return $arr;
    }

    /**
     * 特殊符号的处理
     * @param $arr
     */
    private function dealSpecilfh($arr)
    {
        //&lt; &gt;&amp;&quot;&copy;分别是<，>，&，"，©;
        $arr = array_map(function ($v) {
            return str_replace("&middot;", '·', $v);
        }, $arr);
        $arr = array_map(function ($v) {
            return str_replace("&quot;", '"', $v);
        }, $arr);
        $arr = array_map(function ($v) {
            return str_replace("&lt;", '<', $v);
        }, $arr);
        $arr = array_map(function ($v) {
            return str_replace("&gt;", '>', $v);
        }, $arr);

        return $arr;
    }


    /**
     * 把数组转成数据库中正确的格式
     * @param $arr
     * @return string
     */
    private function deltext($arr)
    {
        $arr = array_map(function ($v) {
            return "<p>{$v}</p>";
        }, $arr);
        $arr = $this->dealSpecilfh($arr);

        return implode($arr);
    }

    function strToUtf8($str)
    {
        $encode = mb_detect_encoding($str, ["ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5']);
        if ($encode == 'UTF-8') {
            return $str;
        } else {
            return mb_convert_encoding($str, 'UTF-8', $encode);
        }
    }

    /**
     * 远程抓取页面内容
     * @param $url
     * @return false|string
     */
    public function getHtml($url)
    {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' =>
                    "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n".
                    "Cookie: \r\n".
                    "Pragma:no-cache\r\n",
            ],
        ];
        $context = stream_context_create($opts);

        $url = preg_replace('# #', '%20', $url);
        $result_data = file_get_contents($url, false, $context);

        return $result_data;

    }

    /**
     * 去除空白
     * @param $str
     * @return string|string[]
     */
    private function myTrim($str)
    {
        $search = [" ", "　", "\n", "\r", "\t"];
        $replace = ["", "", "", "", ""];
        return str_replace($search, $replace, $str);
    }


    public function deal($data, $poetry_content)
    {
        //pinyin audio
        $audiocontent = $data['audiocontent'];
        $title = $data['title'];
        $dynasty = $data['dynasty'];
        $author = $data['author'];
        $content = $data['content'];
        $isaudit = $data['isaudit'];

        //读音用正确的
        $str = $audiocontent ?? $title."。".$dynasty."。".$author."。".$content;
        $str = strip_tags($str);
        $str = $this->dealSpecilfhStr($str);//特殊字符处理

        //汉字用正确的
        $rightStr = $title."。".$dynasty."。".$author."。".$content;
        $rightStr = strip_tags($rightStr);
        $rightStr = $this->dealSpecilfhStr($rightStr);//特殊字符处理

        //读音
        $pronunciation = '';
        if ($str) {
            $arr = mb_str_split(Util::delnbsp($str), 1);
            $rightArr = mb_str_split(Util::delnbsp($rightStr), 1);
            //组合
            $result = [];
            foreach ($arr as $k => $v) {
                $res = $this->poetryService->getPinyin($v);
                $pronunciation .= $res ? $res[0].' ' : $arr[$k];
                $cell = ['zhi' => $rightArr[$k], 'pinyin' => $res ? $res[0] : ''];
                array_push($result, $cell);
            }
            $poetry_content->combination = json_encode($result);
            $poetry_content->pronunciation = $pronunciation;
            //audio
            //if($isaudit==1 && $audiocontent){//后台审核通过并且转语音有内容
            //暂时无审核也可以处理
            $audioPreName = $this->poetryService->getPinyinPermalink(mb_substr($data['title'], 0, 5, 'utf-8'));
            if ($this->is_request_audio) {
                $audioUrl = $this->poetryService->getAudio($audioPreName, $str);
                $poetry_content->audiourl = $audioUrl;
            }


        }
    }

    private function dealSpecilfhStr($str)
    {
        //&lt; &gt;&amp;&quot;&copy;分别是<，>，&，"，©;
        $str = str_replace("&middot;", '·', $str);
        $str = str_replace("&quot;", '"', $str);
        $str = str_replace("&lt;", '<', $str);
        $str = str_replace("&gt;", '>', $str);

        return $str;
    }

}
