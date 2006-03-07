<?php
/*
    p2 - ユーザ設定編集インタフェース
*/

include_once './conf/conf.inc.php';  // 基本設定
require_once P2_LIBRARY_DIR . '/dataphp.class.php';

$_login->authorize(); // ユーザ認証

if (!empty($_POST['submit_save']) || !empty($_POST['submit_default'])) {
    if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId()) {
        die('p2 error: 不正なポストです');
    }
}

//=====================================================================
// 前処理
//=====================================================================

// {{{ ■保存タブが押されていたら、設定を保存

if (!empty($_POST['submit_save'])) {

    // 値の適正チェック、矯正

    // トリム
    $_POST['conf_edit'] = array_map('trim', $_POST['conf_edit']);

    // 選択肢にないもの → デフォルト矯正
    notSelToDef();

    // empty → デフォルト矯正
    emptyToDef();

    // 正の整数 or 0 でないもの → デフォルト矯正
    notIntExceptMinusToDef();

    // 正の実数 or 0 でないもの → デフォルト矯正
    //notFloatExceptMinusToDef();

    /**
     * デフォルト値 $conf_user_def と変更値 $_POST['conf_edit'] の両方が存在していて、
     * デフォルト値と変更値が異なる場合のみ設定保存する（その他のデータは保存されず、破棄される）
     */
    $conf_save = array();
    foreach ($conf_user_def as $k => $v) {
        if (isset($conf_user_def[$k]) && isset($_POST['conf_edit'][$k])) {
            if ($conf_user_def[$k] != $_POST['conf_edit'][$k]) {
                $conf_save[$k] = $_POST['conf_edit'][$k];
            }
        }
    }

    // シリアライズして保存
    FileCtl::make_datafile($_conf['conf_user_file'], $_conf['conf_user_perm']);
    if (file_put_contents($_conf['conf_user_file'], serialize($conf_save), LOCK_EX) === false) {
        $_info_msg_ht .= "<p>×設定を更新保存できませんでした</p>";
    } else {
        $_info_msg_ht .= "<p>○設定を更新保存しました</p>";
        // 変更があれば、内部データも更新しておく
        $_conf = array_merge($_conf, $conf_user_def);
        if (is_array($conf_save)) {
            $_conf = array_merge($_conf, $conf_save);
        }
    }

// }}}
// {{{ ■デフォルトに戻すタブが押されていたら

} elseif (!empty($_POST['submit_default'])) {
    if (file_exists($_conf['conf_user_file']) and unlink($_conf['conf_user_file'])) {
        $_info_msg_ht .= "<p>○設定をデフォルトに戻しました</p>";
        // 変更があれば、内部データも更新しておく
        $_conf = array_merge($_conf, $conf_user_def);
        if (is_array($conf_save)) {
            $_conf = array_merge($_conf, $conf_save);
        }
    }
}

// }}}

//=====================================================================
// プリント設定
//=====================================================================
$ptitle = 'ユーザ設定編集';

$csrfid = P2Util::getCsrfId();

//=====================================================================
// プリント
//=====================================================================
// ヘッダHTMLをプリント
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>\n
EOP;

if (empty($_conf['ktai'])) {
    echo <<<EOP
    <script type="text/javascript" src="js/basic.js"></script>
    <script type="text/javascript" src="js/tabber/tabber.js"></script>
    <script type="text/javascript" src="js/edit_conf_user.js"></script>
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="style/tabber/tabber.css" type="text/css">
    <link rel="stylesheet" href="css.php?css=edit_conf_user&amp;skin={$skin_en}" type="text/css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">\n
EOP;
}

$body_at = ($_conf['ktai']) ? $_conf['k_colors'] : '';
echo <<<EOP
</head>
<body{$body_at}>\n
EOP;

// PC用表示
if (empty($_conf['ktai'])) {
    echo <<<EOP
<p id="pan_menu"><a href="editpref.php">設定管理</a> &gt; {$ptitle}</p>\n
EOP;
}

// 携帯用表示
if (!empty($_conf['ktai'])) {
    $htm['form_submit'] = <<<EOP
<input type="submit" name="submit_save" value="変更を保存する">\n
EOP;
}

// 情報メッセージ表示
if (!empty($_info_msg_ht)) {
    echo $_info_msg_ht;
    $_info_msg_ht = "";
}

echo <<<EOP
<form id="edit_conf_user_form" method="POST" action="{$_SERVER['PHP_SELF']}" target="_self" accept-charset="{$_conf['accept_charset']}">
    {$_conf['k_input_ht']}
    <input type="hidden" name="detect_hint" value="◎◇">
    <input type="hidden" name="csrfid" value="{$csrfid}">\n
EOP;

// PC用表示
if (empty($_conf['ktai'])) {
    echo <<<EOP
<div class="tabber">
<div class="tabbertab" title="rep2基本設定">
<h3>rep2基本設定</h3>
<div class="tabber">\n
EOP;
// 携帯用表示
} else {
    echo $htm['form_submit'];
}

// {{{ rep2基本設定
// {{{ be.2ch.net アカウント

echo getGroupSepaHtml('be.2ch.net アカウント');

echo getEditConfHtml('be_2ch_code', '<a href="http://be.2ch.net/" target="_blank">be.2ch.net</a>の認証コード(パスワードではない)');
echo getEditConfHtml('be_2ch_mail', 'be.2ch.netの登録メールアドレス');

echo getGroupEndHtml();

// }}}
// {{{ PATH

echo getGroupSepaHtml('PATH');

//echo getEditConfHtml('first_page', '右下部分に最初に表示されるページ。オンラインURLも可。');
echo getEditConfHtml('brdfile_online',
    '板リストの指定（オンラインURL）<br>
    板リストをオンラインURLから自動で読み込む。
    指定先は menu.html 形式、2channel.brd 形式のどちらでもよい。
    <!-- 必要なければ、空白に。 --><br>

    2ch基本 <a href="http://menu.2ch.net/bbsmenu.html" target="_blank">http://menu.2ch.net/bbsmenu.html</a><br>
    2ch + 外部BBS <a href="http://azlucky.s25.xrea.com/2chboard/bbsmenu.html" target="_blank">http://azlucky.s25.xrea.com/2chboard/bbsmenu.html</a><br>
    ');

echo getGroupEndHtml();

// }}}
// {{{ subject

echo getGroupSepaHtml('subject');

echo getEditConfHtml('refresh_time', 'スレッド一覧の自動更新間隔 (分指定。0なら自動更新しない)');

echo getEditConfHtml('sb_show_motothre', 'スレッド一覧で未取得スレに対して元スレへのリンク（・）を表示 (する, しない)');
echo getEditConfHtml('sb_show_one', 'スレッド一覧（板表示）で>>1を表示 (する, しない, ニュース系のみ)');
echo getEditConfHtml('sb_show_spd', 'スレッド一覧ですばやさ（レス間隔）を表示 (する, しない)');
echo getEditConfHtml('sb_show_ikioi', 'スレッド一覧で勢い（1日あたりのレス数）を表示 (する, しない)');
echo getEditConfHtml('sb_show_fav', 'スレッド一覧でお気にスレマーク★を表示 (する, しない)');
echo getEditConfHtml('sb_sort_ita', '板表示のスレッド一覧でのデフォルトのソート指定');
echo getEditConfHtml('sort_zero_adjust', '新着ソートでの「既得なし」の「新着数ゼロ」に対するソート優先順位 (上位, 混在, 下位)');
echo getEditConfHtml('cmp_dayres_midoku', '勢いソート時に新着レスのあるスレを優先 (する, しない)');
echo getEditConfHtml('k_sb_disp_range', '携帯閲覧時、一度に表示するスレの数');
echo getEditConfHtml('viewall_kitoku', '既得スレは表示件数に関わらず表示 (する, しない)');

echo getEditConfHtml('sb_ttitle_max_len', 'スレッド一覧で表示するスレッドタイトルの長さの上限 (0で無制限)');
echo getEditConfHtml('sb_ttitle_trim_len', 'スレッドタイトルが長さの上限を越えたとき、この長さまで切り詰める');
echo getEditConfHtml('sb_ttitle_trim_pos', 'スレッドタイトルを切り詰める位置 (先頭, 中央, 末尾)');

echo getGroupEndHtml();

// }}}
// {{{ read

echo getGroupSepaHtml('read');

echo getEditConfHtml('respointer', 'スレ内容表示時、未読の何コ前のレスにポインタを合わせるか');
echo getEditConfHtml('before_respointer', 'PC閲覧時、ポインタの何コ前のレスから表示するか');
echo getEditConfHtml('before_respointer_new', '新着まとめ読みの時、ポインタの何コ前のレスから表示するか');
echo getEditConfHtml('rnum_all_range', '新着まとめ読みで一度に表示するレス数');
echo getEditConfHtml('preview_thumbnail', '画像URLの先読みサムネイルを表示（する, しない)');
echo getEditConfHtml('pre_thumb_limit', '画像URLの先読みサムネイルを一度に表示する制限数 (0で無制限)');
//echo getEditConfHtml('pre_thumb_height', '画像サムネイルの縦の大きさを指定 (ピクセル)');
//echo getEditConfHtml('pre_thumb_width', '画像サムネイルの横の大きさを指定 (ピクセル)');
echo getEditConfHtml('iframe_popup', 'HTMLポップアップ (する, しない, pでする, 画像でする)');
//echo getEditConfHtml('iframe_popup_delay', 'HTMLポップアップの表示遅延時間 (秒)');
echo getEditConfHtml('flex_idpopup', 'ID:xxxxxxxxをIDフィルタリングのリンクに変換 (する, しない)');
echo getEditConfHtml('ext_win_target', '外部サイト等へジャンプする時に開くウィンドウのターゲット名 (同窓:&quot;&quot;, 新窓:&quot;_blank&quot;)');
echo getEditConfHtml('bbs_win_target', 'p2対応BBSサイト内でジャンプする時に開くウィンドウのターゲット名 (同窓:&quot;&quot;, 新窓:&quot;_blank&quot;)');
echo getEditConfHtml('bottom_res_form', 'スレッド下部に書き込みフォームを表示 (する, しない)');
echo getEditConfHtml('quote_res_view', '引用レスを表示 (する, しない)');

echo getEditConfHtml('k_rnum_range', '携帯閲覧時、一度に表示するレスの数');
echo getEditConfHtml('ktai_res_size', '携帯閲覧時、一つのレスの最大表示サイズ');
echo getEditConfHtml('ktai_ryaku_size', '携帯閲覧時、レスを省略したときの表示サイズ');
echo getEditConfHtml('before_respointer_k', '携帯閲覧時、ポインタの何コ前のレスから表示するか');
echo getEditConfHtml('k_use_tsukin', '携帯閲覧時、外部リンクに通勤ブラウザ(通)を利用(する, しない)');
echo getEditConfHtml('k_use_picto', '携帯閲覧時、画像リンクにpic.to(ﾋﾟ)を利用(する, しない)');

echo getEditConfHtml('k_bbs_noname_name', '携帯閲覧時、デフォルトの名無し名を表示（する, しない）');

echo getGroupEndHtml();

// }}}
// {{{ NG/あぼーん

echo getGroupSepaHtml('NG/あぼーん');

echo getEditConfHtml('ngaborn_frequent', '&gt;&gt;1 以外の頻出IDをあぼーんする(する, しない, NGにする)');
echo getEditConfHtml('ngaborn_frequent_one', '&gt;&gt;1 も頻出IDあぼーんの対象外にする(する, しない)');
echo getEditConfHtml('ngaborn_frequent_num', '頻出IDあぼーんのしきい値（出現回数がこれ以上のIDをあぼーん）');
echo getEditConfHtml('ngaborn_frequent_dayres', '勢いの速いスレでは頻出IDあぼーんしない（総レス数/スレ立てからの日数、0なら無効）');
echo getEditConfHtml('ngaborn_chain', '連鎖NGあぼーん(する, しない, あぼーんレスへのレスもNGにする) <br>処理を軽くするため、表示範囲のレスにしか連鎖しない');
echo getEditConfHtml('ngaborn_daylimit', 'この期間、NGあぼーんにHITしなければ、登録ワードを自動的に外す（日数）');

echo getGroupEndHtml();

// }}}
// {{{ ETC

echo getGroupSepaHtml('ETC');

echo getEditConfHtml('my_FROM', 'レス書き込み時のデフォルトの名前');
echo getEditConfHtml('my_mail', 'レス書き込み時のデフォルトのmail');

echo getEditConfHtml('editor_srcfix', 'PC閲覧時、ソースコードのコピペに適した補正をするチェックボックスを表示（する, しない, pc鯖のみ）');

echo getEditConfHtml('get_new_res', '新しいスレッドを取得した時に表示するレス数(全て表示する場合:&quot;all&quot;)');
echo getEditConfHtml('rct_rec_num', '最近読んだスレの記録数');
echo getEditConfHtml('res_hist_rec_num', '書き込み履歴の記録数');
echo getEditConfHtml('res_write_rec', '書き込み内容ログを記録(する, しない)');
echo getEditConfHtml('through_ime', '外部URLジャンプする際に通すゲート (直接, p2 ime(自動転送), p2 ime(手動転送), p2 ime(pのみ手動転送), r.p(自動転送1秒), r.p(自動転送0秒), r.p(手動転送), r.p(pのみ手動転送))');
echo getEditConfHtml('ime_manual_ext', 'ゲートで自動転送しない拡張子（カンマ区切りで、拡張子の前のピリオドは不要）');
echo getEditConfHtml('join_favrank', '<a href="http://akid.s17.xrea.com:8080/favrank/favrank.html" target="_blank">お気にスレ共有</a>に参加(する, しない)');
echo getEditConfHtml('enable_menu_new', '板メニューに新着数を表示 (する, しない, お気に板のみ)');
echo getEditConfHtml('menu_refresh_time', '板メニュー部分の自動更新間隔 (分指定。0なら自動更新しない。)');
echo getEditConfHtml('menu_hide_brds', '板カテゴリ一覧を閉じた状態にする(する, しない)');
//echo getEditConfHtml('brocra_checker_use', 'ブラクラチェッカ(つける, つけない)');
//echo getEditConfHtml('brocra_checker_url', 'ブラクラチェッカURL');
//echo getEditConfHtml('brocra_checker_query', 'ブラクラチェッカのクエリー');
echo getEditConfHtml('enable_exfilter', 'フィルタリングでAND/OR検索を可能にする (off, レスのみ, サブジェクトも)');
echo getEditConfHtml('k_save_packet', '携帯閲覧時、パケット量を減らすため、全角英数・カナ・スペースを半角に変換 (する, しない)');
echo getEditConfHtml('proxy_use', 'プロキシを利用 (する, しない)'); 
echo getEditConfHtml('proxy_host', 'プロキシホスト ex)"127.0.0.1", "www.p2proxy.com"'); 
echo getEditConfHtml('proxy_port', 'プロキシポート ex)"8080"'); 
echo getEditConfHtml('precede_openssl', '●ログインを、まずはopensslで試みる。※PHP 4.3.0以降で、OpenSSLが静的にリンクされている必要がある。');
echo getEditConfHtml('precede_phpcurl', 'curlを使う時、コマンドライン版とPHP関数版どちらを優先するか (コマンドライン版, PHP関数版)');

echo getGroupEndHtml();

// }}}
// {{{ Mobile Color

echo getGroupSepaHtml('Mobile');

echo getEditConfHtml('mobile.background_color', '背景');
echo getEditConfHtml('mobile.text_color', '基本文字色');
echo getEditConfHtml('mobile.link_color', 'リンク');
echo getEditConfHtml('mobile.vlink_color', '訪問済みリンク');
echo getEditConfHtml('mobile.newthre_color', '新着スレッドマーク');
echo getEditConfHtml('mobile.ttitle_color', 'スレッドタイトル');
echo getEditConfHtml('mobile.newres_color', '新着レス番号');
echo getEditConfHtml('mobile.ngword_color', 'NGワード');
echo getEditConfHtml('mobile.onthefly_color', 'オンザフライレス番号');
echo getEditConfHtml('mobile.match_color', 'フィルタリングでマッチしたキーワード');

echo getGroupEndHtml();

// }}}
// }}}

// PC用表示
if (empty($_conf['ktai'])) {
    echo <<<EOP
</div><!-- end of tab -->
</div><!-- end of child tabset "rep2基本設定" -->

<div class="tabbertab" title="拡張パック設定">
<h3>拡張パック設定</h3>
<div class="tabber">\n
EOP;
}

// {{{ 拡張パック設定
// {{{ expack - tGrep

echo getGroupSepaHtml('tGrep');

echo getEditConfHtml('expack.tgrep.quicksearch', '一発検索（表示, 非表示）');
echo getEditConfHtml('expack.tgrep.recent_num', '検索履歴を記録する数（記録しない:0）');
echo getEditConfHtml('expack.tgrep.recent2_num', 'サーチボックスに検索履歴を記録する数、Safari専用（記録しない:0）');

echo getGroupEndHtml();

// }}}
// {{{ expack - スマートポップアップメニュー

echo getGroupSepaHtml('SPM', 'expack.spm.enabled');

echo getEditConfHtml('expack.spm.kokores', 'ここにレス');
echo getEditConfHtml('expack.spm.kokores_orig', 'ここにレスで開くフォームに元レスの内容を表示する');
echo getEditConfHtml('expack.spm.ngaborn', 'あぼーんワード・NGワード登録');
echo getEditConfHtml('expack.spm.ngaborn_confirm', 'あぼーんワード・NGワード登録時に確認する');
echo getEditConfHtml('expack.spm.filter', 'フィルタリング');
echo getEditConfHtml('expack.spm.filter_target', 'フィルタリング結果を開くフレームまたはウインドウ');

echo getGroupEndHtml();

// }}}
// {{{ expack - アクティブモナー

echo getGroupSepaHtml('ActiveMona', 'expack.am.enabled');

if (isset($_conf['expack.am.fontfamily.orig'])) {
    $_conf['expack.am.fontfamily'] = $_conf['expack.am.fontfamily.orig'];
}
echo getEditConfHtml('expack.am.fontfamily', 'AA用のフォント');
echo getEditConfHtml('expack.am.fontsize', 'AA用の文字の大きさ');
echo getEditConfHtml('expack.am.display', 'スイッチを表示する位置');
echo getEditConfHtml('expack.am.autodetect', '自動で判定し、AA用表示をする（PC）');
echo getEditConfHtml('expack.am.autong_k', '自動で判定し、NGワードにする。AAS が有効なら AAS のリンクも作成（携帯）');

echo getGroupEndHtml();

// }}}
// {{{ expack - 入力支援

echo getGroupSepaHtml('入力支援');

echo getEditConfHtml('expack.editor.constant', '定型文');
echo getEditConfHtml('expack.editor.dpreview', 'リアルタイム・プレビュー');
echo getEditConfHtml('expack.editor.check_message', '本文が空でないかチェック');
echo getEditConfHtml('expack.editor.check_sage', 'sageチェック');

echo getGroupEndHtml();

// }}}
// {{{ expack - RSSリーダ

echo getGroupSepaHtml('RSS', 'expack.rss.enabled');

echo getEditConfHtml('expack.rss.check_interval', 'RSSが更新されたかどうか確認する間隔（分指定）');
echo getEditConfHtml('expack.rss.target_frame', 'RSSの外部リンクを開くフレームまたはウインドウ');
echo getEditConfHtml('expack.rss.desc_target_frame', '概要を開くフレームまたはウインドウ');

echo getGroupEndHtml();

// }}}
// {{{ expack - ImageCache2

echo getGroupSepaHtml('ImageCache2', 'expack.ic2.enabled');

echo getEditConfHtml('expack.ic2.through_ime', 'キャッシュに失敗したときの確認用にime経由でソースへのリンクを作成 (する, しない)');
echo getEditConfHtml('expack.ic2.fitimage', 'ポップアップ画像の大きさをウインドウの大きさに合わせる (する, しない, 幅が大きいときだけする, 高さが大きいときだけする, 手動でする)');
echo getEditConfHtml('expack.ic2.pre_thumb_limit_k', '携帯でインライン・サムネイルが有効のときの表示する制限数 (0で無制限)');
echo getEditConfHtml('expack.ic2.newres_ignore_limit', '新着レスの画像は pre_thumb_limit を無視して全て表示 (する, しない)');
echo getEditConfHtml('expack.ic2.newres_ignore_limit_k', '携帯で新着レスの画像は pre_thumb_limit_k を無視して全て表示 (する, しない)');

echo getGroupEndHtml();

// }}}
// {{{ expack - Google検索

echo getGroupSepaHtml('Google検索', 'expack.google.enabled');

echo getEditConfHtml('expack.google.key', 'Google Web APIs の登録キー');

echo getGroupEndHtml();

// }}}
// {{{ expack - AAS

echo getGroupSepaHtml('AAS', 'expack.aas.enabled');

echo getEditConfHtml('expack.aas.inline', '携帯で自動 AA 判定と連動し、インライン表示 (する, しない)');
echo getEditConfHtml('expack.aas.image_type', '画像形式 (PNG, JPEG, GIF)');
echo getEditConfHtml('expack.aas.jpeg_quality', 'JPEGの品質 (0-100)');
echo getEditConfHtml('expack.aas.image_width', '携帯用の画像の横幅 (ピクセル)');
echo getEditConfHtml('expack.aas.image_height', '携帯用の画像の高さ (ピクセル)');
echo getEditConfHtml('expack.aas.image_width_pc', 'PC用の画像の横幅 (ピクセル)');
echo getEditConfHtml('expack.aas.image_height_pc', 'PC用の画像の高さ (ピクセル)');
echo getEditConfHtml('expack.aas.image_width_il', 'インライン画像の横幅 (ピクセル)');
echo getEditConfHtml('expack.aas.image_height_il', 'インライン画像の高さ (ピクセル)');
echo getEditConfHtml('expack.aas.trim', '画像の余白をトリミング (する, しない)');
echo getEditConfHtml('expack.aas.bold', '太字 (する, しない)');
echo getEditConfHtml('expack.aas.fgcolor', '文字色 (6桁または3桁の16進数)');
echo getEditConfHtml('expack.aas.bgcolor', '背景色 (6桁または3桁の16進数)');
echo getEditConfHtml('expack.aas.max_fontsize', '最大の文字サイズ (ポイント)');
echo getEditConfHtml('expack.aas.min_fontsize', '最小の文字サイズ (ポイント)');
echo getEditConfHtml('expack.aas.inline_fontsize', 'インライン表示の文字サイズ (ポイント)');

echo getGroupEndHtml();

// }}}
// }}}

// PC用表示
if (empty($_conf['ktai'])) {
    echo <<<EOP
</div><!-- end of tab -->
</div><!-- end of child tabset "拡張パック設定" -->
</div><!-- end of parent tabset -->\n
EOP;
// 携帯用表示
} else {
    echo $htm['form_submit'];
}

echo '</form>'."\n";


// 携帯なら
if ($_conf['ktai']) {
    echo <<<EOP
<hr>
<a {$_conf['accesskey']}="{$_conf['k_accesskey']['up']}" href="editpref.php{$_conf['k_at_q']}">{$_conf['k_accesskey']['up']}.設定編集</a>
{$_conf['k_to_index_ht']}
EOP;
}

echo '</body></html>';

// ■ここまで
exit;

//=====================================================================
// 関数
//=====================================================================

/**
 * ルール設定（$conf_user_rules）に基づいて、
 * 指定のnameにおいて、POST指定がemptyの時は、デフォルトセットする
 */
function emptyToDef()
{
    global $conf_user_def, $conf_user_rules;

    $rule = 'NotEmpty';

    if (is_array($conf_user_rules)) {
        foreach ($conf_user_rules as $n => $va) {
            if (in_array($rule, $va)) {
                if (isset($_POST['conf_edit'][$n])) {
                    if (empty($_POST['conf_edit'][$n])) {
                        $_POST['conf_edit'][$n] = $conf_user_def[$n];
                    }
                }
            }
        } // foreach
    }
    return true;
}

/**
 * ルール設定（$conf_user_rules）に基づいて、
 * POST指定を正の整数化できる時は正の整数化（0を含む）し、
 * できない時は、デフォルトセットする
 */
function notIntExceptMinusToDef()
{
    global $conf_user_def, $conf_user_rules;

    $rule = 'IntExceptMinus';

    if (is_array($conf_user_rules)) {
        foreach ($conf_user_rules as $n => $va) {
            if (in_array($rule, $va)) {
                if (isset($_POST['conf_edit'][$n])) {
                    // 全角→半角 矯正
                    $_POST['conf_edit'][$n] = mb_convert_kana($_POST['conf_edit'][$n], 'a');
                    // 整数化できるなら
                    if (is_numeric($_POST['conf_edit'][$n])) {
                        // 整数化する
                        $_POST['conf_edit'][$n] = intval($_POST['conf_edit'][$n]);
                        // 負の数はデフォルトに
                        if ($_POST['conf_edit'][$n] < 0) {
                            $_POST['conf_edit'][$n] = intval($conf_user_def[$n]);
                        }
                    // 整数化できないものは、デフォルトに
                    } else {
                        $_POST['conf_edit'][$n] = intval($conf_user_def[$n]);
                    }
                }
            }
        } // foreach
    }
    return true;
}

/**
 * ルール設定（$conf_user_rules）に基づいて、
 * POST指定を正の実数化できる時は正の実数化（0を含む）し、
 * できない時は、デフォルトセットする
 */
/*function notFloatExceptMinusToDef()
{
    global $conf_user_def, $conf_user_rules;

    $rule = 'FloatExceptMinus';

    if (is_array($conf_user_rules)) {
        foreach ($conf_user_rules as $n => $va) {
            if (in_array($rule, $va)) {
                if (isset($_POST['conf_edit'][$n])) {
                    // 全角→半角 矯正
                    $_POST['conf_edit'][$n] = mb_convert_kana($_POST['conf_edit'][$n], 'a');
                    // 実数化できるなら
                    if (is_numeric($_POST['conf_edit'][$n])) {
                        // 実数化する
                        $_POST['conf_edit'][$n] = floatval($_POST['conf_edit'][$n]);
                        // 負の数 or 無効な数値はデフォルトに
                        if (!is_finite($_POST['conf_edit'][$n]) || $_POST['conf_edit'][$n] < 0) {
                            $_POST['conf_edit'][$n] = floatval($conf_user_def[$n]);
                        }
                    // 実数化できないものは、デフォルトに
                    } else {
                        $_POST['conf_edit'][$n] = floatval($conf_user_def[$n]);
                    }
                }
            }
        } // foreach
    }
    return true;
}*/

/**
 * 選択肢にない値はデフォルトセットする
 */
function notSelToDef()
{
    global $conf_user_def, $conf_user_sel, $conf_user_rad;

    $conf_user_list = array_merge($conf_user_sel, $conf_user_rad);
    $names = array_keys($conf_user_list);

    if (is_array($names)) {
        foreach ($names as $n) {
            if (isset($_POST['conf_edit'][$n])) {
                if (!array_key_exists($_POST['conf_edit'][$n], $conf_user_list[$n])) {
                    $_POST['conf_edit'][$n] = $conf_user_def[$n];
                }
            }
        } // foreach
    }
    return true;
}

/**
 * グループ分け用のHTMLを得る（関数内でPC、携帯用表示を振り分け）
 */
function getGroupSepaHtml($title, $conf_key = null)
{
    global $_conf;

    // PC用
    if (empty($_conf['ktai'])) {
        $ht = <<<EOP
<div class="tabbertab" title="{$title}">
<h4>{$title}</h4>\n
EOP;
        if (!empty($conf_key) && empty($_conf[$conf_key])) {
            $ht .= <<<EOP
<p><i>現在、この機能は無効になっています。<br>
有効にするには conf/conf_admin_ex.inc.php で \$_conf[{$conf_key}] を on にしてください。</i></p>\n
EOP;
        }
        $ht .= <<<EOP
<table class="edit_conf_user" cellspacing="0">
    <tr>
        <th>変数名</th>
        <th>値</th>
        <th>説明</th>
    </tr>\n
EOP;
    // 携帯用
    } else {
        $ht = "<hr><h4>{$title}</h4>"."\n";
    }
    return $ht;
}

/**
 * グループ終端のHTMLを得る（携帯では空）
 */
function getGroupEndHtml()
{
    global $_conf;

    // PC用
    if (empty($_conf['ktai'])) {
        $ht = <<<EOP
    <tr class="group">
        <td colspan="3" align="center">
            <input type="submit" name="submit_save" value="変更を保存する">
            <input type="reset" value="変更を取り消す" onclick="if (!window.confirm('変更を取り消してもよろしいですか？（他のタブの変更もリセットされます）')) {return false;}">
            <input type="submit" name="submit_default" value="デフォルトに戻す" onclick="if (!window.confirm('ユーザ設定をデフォルトに戻してもよろしいですか？（やり直しはできません）')) {return false;}">
        </td>
    </tr>
</table>
</div><!-- end of tab -->\n
EOP;
    // 携帯用
    } else {
        $ht = '';
    }
    return $ht;
}

/**
 * 編集フォームinput用HTMLを得る（関数内でPC、携帯用表示を振り分け）
 */
function getEditConfHtml($name, $description_ht)
{
    global $_conf, $conf_user_def, $conf_user_sel, $conf_user_rad;

    // デフォルト値の規定がなければ、空白を返す
    if (!isset($conf_user_def[$name])) {
        return '';
    }

    $name_view = htmlspecialchars($_conf[$name], ENT_QUOTES);

    if (empty($_conf['ktai'])) {
        $input_size_at = ' size="38"';
    } else {
        $input_size_at = '';
    }

    // select 選択形式なら
    if ($conf_user_sel[$name]) {
        $form_ht = getEditConfSelHtml($name);
        $key = $conf_user_def[$name];
        $def_views[$name] = htmlspecialchars($conf_user_sel[$name][$key], ENT_QUOTES);
    // select 選択形式なら
    } elseif ($conf_user_rad[$name]) {
        $form_ht = getEditConfRadHtml($name);
        $key = $conf_user_def[$name];
        $def_views[$name] = htmlspecialchars($conf_user_rad[$name][$key], ENT_QUOTES);
    // input 入力式なら
    } else {
        $form_ht = <<<EOP
<input type="text" name="conf_edit[{$name}]" value="{$name_view}"{$input_size_at}>\n
EOP;
        if (is_string($conf_user_def[$name])) {
            $def_views[$name] = htmlspecialchars($conf_user_def[$name], ENT_QUOTES);
        } else {
            $def_views[$name] = $conf_user_def[$name];
        }
    }

    // PC用
    if (empty($_conf['ktai'])) {
        $r = <<<EOP
    <tr title="デフォルト値: {$def_views[$name]}">
        <td>{$name}</td>
        <td>{$form_ht}</td>
        <td>{$description_ht}</td>
    </tr>\n
EOP;
    // 携帯用
    } else {
        $r = <<<EOP
[{$name}]<br>
{$description_ht}<br>
{$form_ht}<br>
<br>\n
EOP;
    }

    return $r;
}

/**
 * 編集フォームselect用HTMLを得る
 */
function getEditConfSelHtml($name)
{
    global $_conf, $conf_user_def, $conf_user_sel;

    foreach ($conf_user_sel[$name] as $key => $value) {
        /*
        if ($value == "") {
            continue;
        }
        */
        $selected = "";
        if ($_conf[$name] == $key) {
            $selected = " selected";
        }
        $key_ht = htmlspecialchars($key, ENT_QUOTES);
        $value_ht = htmlspecialchars($value, ENT_QUOTES);
        $options_ht .= "\t<option value=\"{$key_ht}\"{$selected}>{$value_ht}</option>\n";
    } // foreach

    $form_ht = <<<EOP
        <select name="conf_edit[{$name}]">
        {$options_ht}
        </select>\n
EOP;
    return $form_ht;
}

/**
 * 編集フォームradio用HTMLを得る
 */
function getEditConfRadHtml($name)
{
    global $_conf, $conf_user_def, $conf_user_rad;

    $form_ht = '';

    foreach ($conf_user_rad[$name] as $key => $value) {
        /*
        if ($value == "") {
            continue;
        }
        */
        $checked = "";
        if ($_conf[$name] == $key) {
            $checked = " checked";
        }
        $key_ht = htmlspecialchars($key, ENT_QUOTES);
        $value_ht = htmlspecialchars($value, ENT_QUOTES);
        $form_ht .= "<label><input type=\"radio\" name=\"conf_edit[{$name}]\" value=\"{$key_ht}\"{$checked}>{$value_ht}</label>\n";
    } // foreach

    return $form_ht;
}

?>
