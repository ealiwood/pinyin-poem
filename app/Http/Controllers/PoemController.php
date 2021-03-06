<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Poem;
use Pinyin;
use App\Services\AipSpeech;
use App\Mail\Poem as PoemMail;
use Illuminate\Support\Facades\Auth;

class PoemController extends Controller
{
    /**
     * index 
    */
    public function index(Request $request)
    {
        // todo 
    }


    /**
     * show
     */
    public function show(Request $request, $id)
    {
        $poem = Poem::find($id);
        $contents = $this->formatContent($poem);

        return view('poem', compact('poem', 'contents'));
    }

    /**
     * 读诗
     */
    public function speech(Request $request, $id)
    {
        $ai = new AipSpeech();
        $path = $ai->getVoice('这个是测试的', 'haha');
        return $path;
    }
    
    /**
     * 发送指定诗的邮件
     */
    public function mail(Request $request, $id)
    {
        $poem = Poem::find($id);
        $contents = $this->formatContent($poem);
        $email = Auth::user()->email;

        return \Mail::to($email)->send(new PoemMail($poem, $contents));
    }


    /**
     * 传来诗，然后处理返回规整好的内容
     */
    public function formatContent($poem)
    {
        $contentStr = preg_replace('/\s/', '', $poem->content);
        $pinyinStr = str_replace('、', ' 、',
            str_replace('?', ' ?',
                str_replace('!', ' !',
                    str_replace('.', ' .',
                        str_replace(',', ' ,', Pinyin::sentence($contentStr, true)
                        )
                    )
                )
            )
        );
        $pinyinSeparator = strpos(' !', $pinyinStr) ? ' !' : ' .';
        $pinyinArray = explode($pinyinSeparator, $pinyinStr);

        $contentSeparator = strpos('！', $contentStr) ? '！' : '。';
        $contentArray = explode($contentSeparator, $contentStr);

        // 整首诗分割之后组成成带拼音的
        $results = [];
        unset($pinyinArray[count($pinyinArray) - 1]);

        foreach ($pinyinArray as $key => $val) {
            $fixPinyinStr = trim($val . $pinyinSeparator);
            $formatPinyin = explode(' ', $fixPinyinStr);

            $fixContentStr = trim($contentArray[$key] . $contentSeparator);
            $formatText = array_filter(str_split($fixContentStr, 3));

            // 由于可能有相同键值，无法直接使用 array_combine 合并
            $line = [];
            foreach (array_filter($formatPinyin) as $kk => $vv) {
                $line[] = [$formatText[$kk], $formatPinyin[$kk]];
            }

            $results[] = $line;
        }

        return $results;
    }
}
