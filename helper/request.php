<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @author       List of devices taken here: http://code.google.com/p/php-mobile-detect/wiki/Mobile_Detect
 * @version      1.1.2
 * @package      Nex
 * @subpackage   core
 *
 * @update (01/06/2010) [Mikael Laforge] - 1.0.0 - Script Creation
 * @update (10/07/2012) [ML] - 1.0.1 - Added is_https() method
 * @update (21/08/2012) [ML] - 1.0.2 - Added is_mobile() method
 * @update (30/10/2012) [ML] - 1.0.3 - improved mobile detection
 *                                      added is_tablet() method
 *                                      fixed a bug in is_bot() method
 * @update (27/06/2013) [ML] - 1.1.0 - updated list of devices
 *                                     is_mobile() method will now perform a general lookup on OS and User agent to find out if the device is mobile or not. Its not restricted to phones anymore
 *                                     added is_phone() method which perform the same look up as the old is_mobile() method
 * @update (13/08/2014) [ML] - 1.1.1 - added method is_json()
 * @update (05/09/2014) [ML] - 1.1.2 - will now correctly detect IE 11+
 * @update (03/09/2016) [DM] - 1.2.0 - Added is_post() and is_get() to remove tokens in the Admin section
 *
 * This class was made to help with http requests
 */

abstract class request {
    protected static $engine ;
    protected static $browser ;
    protected static $version ;
    protected static $platform ;
    protected static $is_mobile ;
    protected static $is_tablet ;
 
    // List of mobile devices (phones)
    protected static $phoneDevices = array(
        'iPhone' => '\biPhone.*Mobile|\biPod|\biTunes',
        'BlackBerry' => 'BlackBerry|\bBB10\b|rim[0-9]+',
        'HTC' => 'HTC|HTC.*(Sensation|Evo|Vision|Explorer|6800|8100|8900|A7272|S510e|C110e|Legend|Desire|T8282)|APX515CKT|Qtek9090|APA9292KT|HD_mini|Sensation.*Z710e|PG86100|Z715e|Desire.*(A8181|HD)|ADR6200|ADR6425|001HT|Inspire 4G|Android.*\bEVO\b',
        'Nexus' => 'Nexus One|Nexus S|Galaxy.*Nexus|Android.*Nexus.*Mobile',
        // @todo: Is 'Dell Streak' a tablet or a phone? ;)
        'Dell' => 'Dell.*Streak|Dell.*Aero|Dell.*Venue|DELL.*Venue Pro|Dell Flash|Dell Smoke|Dell Mini 3iX|XCD28|XCD35|\b001DL\b|\b101DL\b|\bGS01\b',
        'Motorola' => 'Motorola|\bDroid\b.*Build|DROIDX|Android.*Xoom|HRI39|MOT-|A1260|A1680|A555|A853|A855|A953|A955|A956|Motorola.*ELECTRIFY|Motorola.*i1|i867|i940|MB200|MB300|MB501|MB502|MB508|MB511|MB520|MB525|MB526|MB611|MB612|MB632|MB810|MB855|MB860|MB861|MB865|MB870|ME501|ME502|ME511|ME525|ME600|ME632|ME722|ME811|ME860|ME863|ME865|MT620|MT710|MT716|MT720|MT810|MT870|MT917|Motorola.*TITANIUM|WX435|WX445|XT300|XT301|XT311|XT316|XT317|XT319|XT320|XT390|XT502|XT530|XT531|XT532|XT535|XT603|XT610|XT611|XT615|XT681|XT701|XT702|XT711|XT720|XT800|XT806|XT860|XT862|XT875|XT882|XT883|XT894|XT909|XT910|XT912|XT928',
        'Samsung' => 'Samsung|SGH-I337|BGT-S5230|GT-B2100|GT-B2700|GT-B2710|GT-B3210|GT-B3310|GT-B3410|GT-B3730|GT-B3740|GT-B5510|GT-B5512|GT-B5722|GT-B6520|GT-B7300|GT-B7320|GT-B7330|GT-B7350|GT-B7510|GT-B7722|GT-B7800|GT-C3010|GT-C3011|GT-C3060|GT-C3200|GT-C3212|GT-C3212I|GT-C3262|GT-C3222|GT-C3300|GT-C3300K|GT-C3303|GT-C3303K|GT-C3310|GT-C3322|GT-C3330|GT-C3350|GT-C3500|GT-C3510|GT-C3530|GT-C3630|GT-C3780|GT-C5010|GT-C5212|GT-C6620|GT-C6625|GT-C6712|GT-E1050|GT-E1070|GT-E1075|GT-E1080|GT-E1081|GT-E1085|GT-E1087|GT-E1100|GT-E1107|GT-E1110|GT-E1120|GT-E1125|GT-E1130|GT-E1160|GT-E1170|GT-E1175|GT-E1180|GT-E1182|GT-E1200|GT-E1210|GT-E1225|GT-E1230|GT-E1390|GT-E2100|GT-E2120|GT-E2121|GT-E2152|GT-E2220|GT-E2222|GT-E2230|GT-E2232|GT-E2250|GT-E2370|GT-E2550|GT-E2652|GT-E3210|GT-E3213|GT-I5500|GT-I5503|GT-I5700|GT-I5800|GT-I5801|GT-I6410|GT-I6420|GT-I7110|GT-I7410|GT-I7500|GT-I8000|GT-I8150|GT-I8160|GT-I8320|GT-I8330|GT-I8350|GT-I8530|GT-I8700|GT-I8703|GT-I8910|GT-I9000|GT-I9001|GT-I9003|GT-I9010|GT-I9020|GT-I9023|GT-I9070|GT-I9100|GT-I9103|GT-I9220|GT-I9250|GT-I9300|GT-I9505|GT-M3510|GT-M5650|GT-M7500|GT-M7600|GT-M7603|GT-M8800|GT-M8910|GT-N7000|GT-S3110|GT-S3310|GT-S3350|GT-S3353|GT-S3370|GT-S3650|GT-S3653|GT-S3770|GT-S3850|GT-S5210|GT-S5220|GT-S5229|GT-S5230|GT-S5233|GT-S5250|GT-S5253|GT-S5260|GT-S5263|GT-S5270|GT-S5300|GT-S5330|GT-S5350|GT-S5360|GT-S5363|GT-S5369|GT-S5380|GT-S5380D|GT-S5560|GT-S5570|GT-S5600|GT-S5603|GT-S5610|GT-S5620|GT-S5660|GT-S5670|GT-S5690|GT-S5750|GT-S5780|GT-S5830|GT-S5839|GT-S6102|GT-S6500|GT-S7070|GT-S7200|GT-S7220|GT-S7230|GT-S7233|GT-S7250|GT-S7500|GT-S7530|GT-S7550|GT-S7562|GT-S8000|GT-S8003|GT-S8500|GT-S8530|GT-S8600|SCH-A310|SCH-A530|SCH-A570|SCH-A610|SCH-A630|SCH-A650|SCH-A790|SCH-A795|SCH-A850|SCH-A870|SCH-A890|SCH-A930|SCH-R950|SCH-A950|SCH-A970|SCH-A990|SCH-I100|SCH-I110|SCH-I400|SCH-I405|SCH-I500|SCH-I510|SCH-I515|SCH-I600|SCH-I730|SCH-I760|SCH-I770|SCH-I830|SCH-I910|SCH-I920|SCH-LC11|SCH-N150|SCH-N300|SCH-R100|SCH-R300|SCH-R351|SCH-R400|SCH-R410|SCH-T300|SCH-U310|SCH-U320|SCH-U350|SCH-U360|SCH-U365|SCH-U370|SCH-U380|SCH-U410|SCH-U430|SCH-U450|SCH-U460|SCH-U470|SCH-U490|SCH-U540|SCH-U550|SCH-U620|SCH-U640|SCH-U650|SCH-U660|SCH-U700|SCH-U740|SCH-U750|SCH-U810|SCH-U820|SCH-U900|SCH-U940|SCH-U960|SCS-26UC|SGH-A107|SGH-A117|SGH-A127|SGH-A137|SGH-A157|SGH-A167|SGH-A177|SGH-A187|SGH-A197|SGH-A227|SGH-A237|SGH-A257|SGH-A437|SGH-A517|SGH-A597|SGH-A637|SGH-A657|SGH-A667|SGH-A687|SGH-A697|SGH-A707|SGH-A717|SGH-A727|SGH-A737|SGH-A747|SGH-A767|SGH-A777|SGH-A797|SGH-A817|SGH-A827|SGH-A837|SGH-A847|SGH-A867|SGH-A877|SGH-A887|SGH-A897|SGH-A927|SGH-B100|SGH-B130|SGH-B200|SGH-B220|SGH-C100|SGH-C110|SGH-C120|SGH-C130|SGH-C140|SGH-C160|SGH-C170|SGH-C180|SGH-C200|SGH-C207|SGH-C210|SGH-C225|SGH-C230|SGH-C417|SGH-C450|SGH-D307|SGH-D347|SGH-D357|SGH-D407|SGH-D415|SGH-D780|SGH-D807|SGH-D980|SGH-E105|SGH-E200|SGH-E315|SGH-E316|SGH-E317|SGH-E335|SGH-E590|SGH-E635|SGH-E715|SGH-E890|SGH-F300|SGH-F480|SGH-I200|SGH-I300|SGH-I317|SGH-I320|SGH-I550|SGH-I577|SGH-I600|SGH-I607|SGH-I617|SGH-I627|SGH-I637|SGH-I677|SGH-I700|SGH-I717|SGH-I727|SGH-i747M|SGH-I777|SGH-I780|SGH-I827|SGH-I847|SGH-I857|SGH-I896|SGH-I897|SGH-I900|SGH-I907|SGH-I917|SGH-I927|SGH-I937|SGH-I997|SGH-J150|SGH-J200|SGH-L170|SGH-L700|SGH-M110|SGH-M150|SGH-M200|SGH-N105|SGH-N500|SGH-N600|SGH-N620|SGH-N625|SGH-N700|SGH-N710|SGH-P107|SGH-P207|SGH-P300|SGH-P310|SGH-P520|SGH-P735|SGH-P777|SGH-Q105|SGH-R210|SGH-R220|SGH-R225|SGH-S105|SGH-S307|SGH-T109|SGH-T119|SGH-T139|SGH-T209|SGH-T219|SGH-T229|SGH-T239|SGH-T249|SGH-T259|SGH-T309|SGH-T319|SGH-T329|SGH-T339|SGH-T349|SGH-T359|SGH-T369|SGH-T379|SGH-T409|SGH-T429|SGH-T439|SGH-T459|SGH-T469|SGH-T479|SGH-T499|SGH-T509|SGH-T519|SGH-T539|SGH-T559|SGH-T589|SGH-T609|SGH-T619|SGH-T629|SGH-T639|SGH-T659|SGH-T669|SGH-T679|SGH-T709|SGH-T719|SGH-T729|SGH-T739|SGH-T746|SGH-T749|SGH-T759|SGH-T769|SGH-T809|SGH-T819|SGH-T839|SGH-T919|SGH-T929|SGH-T939|SGH-T959|SGH-T989|SGH-U100|SGH-U200|SGH-U800|SGH-V205|SGH-V206|SGH-X100|SGH-X105|SGH-X120|SGH-X140|SGH-X426|SGH-X427|SGH-X475|SGH-X495|SGH-X497|SGH-X507|SGH-X600|SGH-X610|SGH-X620|SGH-X630|SGH-X700|SGH-X820|SGH-X890|SGH-Z130|SGH-Z150|SGH-Z170|SGH-ZX10|SGH-ZX20|SHW-M110|SPH-A120|SPH-A400|SPH-A420|SPH-A460|SPH-A500|SPH-A560|SPH-A600|SPH-A620|SPH-A660|SPH-A700|SPH-A740|SPH-A760|SPH-A790|SPH-A800|SPH-A820|SPH-A840|SPH-A880|SPH-A900|SPH-A940|SPH-A960|SPH-D600|SPH-D700|SPH-D710|SPH-D720|SPH-I300|SPH-I325|SPH-I330|SPH-I350|SPH-I500|SPH-I600|SPH-I700|SPH-L700|SPH-M100|SPH-M220|SPH-M240|SPH-M300|SPH-M305|SPH-M320|SPH-M330|SPH-M350|SPH-M360|SPH-M370|SPH-M380|SPH-M510|SPH-M540|SPH-M550|SPH-M560|SPH-M570|SPH-M580|SPH-M610|SPH-M620|SPH-M630|SPH-M800|SPH-M810|SPH-M850|SPH-M900|SPH-M910|SPH-M920|SPH-M930|SPH-N100|SPH-N200|SPH-N240|SPH-N300|SPH-N400|SPH-Z400|SWC-E100|SCH-i909|GT-N7100|SCH-I535',
        'LG' => '\bLG\b;|(LG|LG-)?(C800|C900|E400|E610|E900|E-900|F160|F180K|F180L|F180S|730|855|L160|LS840|LS970|LU6200|MS690|MS695|MS770|MS840|MS870|MS910|P500|P700|P705|VM696|AS680|AS695|AX840|C729|E970|GS505|272|C395|E739BK|E960|L55C|L75C|LS696|LS860|P769BK|P350|P870|UN272|US730|VS840|VS950|LN272|LN510|LS670|LS855|LW690|MN270|MN510|P509|P769|P930|UN200|UN270|UN510|UN610|US670|US740|US760|UX265|UX840|VN271|VN530|VS660|VS700|VS740|VS750|VS910|VS920|VS930|VX9200|VX11000|AX840A|LW770|P506|P925|P999)',
        'Sony' => 'sony|SonyEricsson|SonyEricssonLT15iv|LT18i|E10i',
        'Asus' => 'Asus.*Galaxy',
        'Palm' => 'PalmSource|Palm', // avantgo|blazer|elaine|hiptop|plucker|xiino ; @todo - complete the regex.
        'Vertu' => 'Vertu|Vertu.*Ltd|Vertu.*Ascent|Vertu.*Ayxta|Vertu.*Constellation(F|Quest)?|Vertu.*Monika|Vertu.*Signature', // Just for fun ;)
        // @ref: http://www.pantech.co.kr/en/prod/prodList.do?gbrand=VEGA (PANTECH)
        // Most of the VEGA devices are legacy. PANTECH seem to be newer devices based on Android.
        'Pantech' => 'PANTECH|IM-A850S|IM-A840S|IM-A830L|IM-A830K|IM-A830S|IM-A820L|IM-A810K|IM-A810S|IM-A800S|IM-T100K|IM-A725L|IM-A780L|IM-A775C|IM-A770K|IM-A760S|IM-A750K|IM-A740S|IM-A730S|IM-A720L|IM-A710K|IM-A690L|IM-A690S|IM-A650S|IM-A630K|IM-A600S|VEGA PTL21|PT003|P8010|ADR910L|P6030|P6020|P9070|P4100|P9060|P5000|CDM8992|TXT8045|ADR8995|IS11PT|P2030|P6010|P8000|PT002|IS06|CDM8999|P9050|PT001|TXT8040|P2020|P9020|P2000|P7040|P7000|C790',
        // @ref: http://www.fly-phone.com/devices/smartphones/ ; Included only smartphones.
        'Fly' => 'IQ230|IQ444|IQ450|IQ440|IQ442|IQ441|IQ245|IQ256|IQ236|IQ255|IQ235|IQ245|IQ275|IQ240|IQ285|IQ280|IQ270|IQ260|IQ250',
        // Added simvalley mobile just for fun. They have some interesting devices.
        // @ref: http://www.simvalley.fr/telephonie---gps-_22_telephonie-mobile_telephones_.html
        'SimValley' => '\b(SP-80|XT-930|SX-340|XT-930|SX-310|SP-360|SP60|SPT-800|SP-120|SPT-800|SP-140|SPX-5|SPX-8|SP-100|SPX-8|SPX-12)\b',
        // @Tapatalk is a mobile app; @ref: http://support.tapatalk.com/threads/smf-2-0-2-os-and-browser-detection-plugin-and-tapatalk.15565/#post-79039
        'GenericPhone' => 'Tapatalk|PDA;|PPC;|SAGEM|mmp|pocket|psp|symbian|Smartphone|smartfon|treo|up.browser|up.link|vodafone|wap|nokia|Series40|Series60|S60|SonyEricsson|N900|MAUI.*WAP.*Browser|LG-P500'
    );
 
    // List of tablet devices.
    protected static $tabletDevices = array(
        'iPad' => 'iPad|iPad.*Mobile', // @todo: check for mobile friendly emails topic.
        'NexusTablet' => '^.*Android.*Nexus(((?:(?!Mobile))|(?:(\s(7|10).+))).)*$',
        'SamsungTablet' => 'SAMSUNG.*Tablet|Galaxy.*Tab|SC-01C|GT-P1000|GT-P1003|GT-P1010|GT-P3105|GT-P6210|GT-P6800|GT-P6810|GT-P7100|GT-P7300|GT-P7310|GT-P7500|GT-P7510|SCH-I800|SCH-I815|SCH-I905|SGH-I957|SGH-I987|SGH-T849|SGH-T859|SGH-T869|SPH-P100|GT-P3100|GT-P3108|GT-P3110|GT-P5100|GT-P5110|GT-P6200|GT-P7320|GT-P7511|GT-N8000|GT-P8510|SGH-I497|SPH-P500|SGH-T779|SCH-I705|SCH-I915|GT-N8013|GT-P3113|GT-P5113|GT-P8110|GT-N8010|GT-N8005|GT-N8020|GT-P1013|GT-P6201|GT-P7501|GT-N5100|GT-N5110|SHV-E140K|SHV-E140L|SHV-E140S|SHV-E150S|SHV-E230K|SHV-E230L|SHV-E230S|SHW-M180K|SHW-M180L|SHW-M180S|SHW-M180W|SHW-M300W|SHW-M305W|SHW-M380K|SHW-M380S|SHW-M380W|SHW-M430W|SHW-M480K|SHW-M480S|SHW-M480W|SHW-M485W|SHW-M486W|SHW-M500W|GT-I9228|SCH-P739|SCH-I925',
        // @reference: http://www.labnol.org/software/kindle-user-agent-string/20378/
        'Kindle' => 'Kindle|Silk.*Accelerated',
        // Only the Surface tablets with Windows RT are considered mobile.
        // @ref: http://msdn.microsoft.com/en-us/library/ie/hh920767(v=vs.85).aspx
        'SurfaceTablet' => 'Windows NT [0-9.]+; ARM;',
        'AsusTablet' => 'Transformer|TF101',
        'BlackBerryTablet' => 'PlayBook|RIM Tablet',
        'HTCtablet' => 'HTC Flyer|HTC Jetstream|HTC-P715a|HTC EVO View 4G|PG41200',
        'MotorolaTablet' => 'xoom|sholest|MZ615|MZ605|MZ505|MZ601|MZ602|MZ603|MZ604|MZ606|MZ607|MZ608|MZ609|MZ615|MZ616|MZ617',
        'NookTablet' => 'Android.*Nook|NookColor|nook browser|BNRV200|BNRV200A|BNTV250|BNTV250A|LogicPD Zoom2',
        // @ref: http://www.acer.ro/ac/ro/RO/content/drivers
        // @ref: http://www.packardbell.co.uk/pb/en/GB/content/download (Packard Bell is part of Acer)
        'AcerTablet' => 'Android.*\b(A100|A101|A110|A200|A210|A211|A500|A501|A510|A511|A700|A701|W500|W500P|W501|W501P|W510|W511|W700|G100|G100W|B1-A71)\b',
        // @ref: http://eu.computers.toshiba-europe.com/innovation/family/Tablets/1098744/banner_id/tablet_footerlink/
        // @ref: http://us.toshiba.com/tablets/tablet-finder
        // @ref: http://www.toshiba.co.jp/regza/tablet/
        'ToshibaTablet' => 'Android.*(AT100|AT105|AT200|AT205|AT270|AT275|AT300|AT305|AT1S5|AT500|AT570|AT700|AT830)|TOSHIBA.*FOLIO',
        // @ref: http://www.nttdocomo.co.jp/english/service/developer/smart_phone/technical_info/spec/index.html
        'LGTablet' => '\bL-06C|LG-V900|LG-V909\b',
        // Prestigio Tablets http://www.prestigio.com/support
        'PrestigioTablet' => 'PMP3170B|PMP3270B|PMP3470B|PMP7170B|PMP3370B|PMP3570C|PMP5870C|PMP3670B|PMP5570C|PMP5770D|PMP3970B|PMP3870C|PMP5580C|PMP5880D|PMP5780D|PMP5588C|PMP7280C|PMP7280|PMP7880D|PMP5597D|PMP5597|PMP7100D|PER3464|PER3274|PER3574|PER3884|PER5274|PER5474',
        'YarvikTablet' => 'Android.*(TAB210|TAB211|TAB224|TAB250|TAB260|TAB264|TAB310|TAB360|TAB364|TAB410|TAB411|TAB420|TAB424|TAB450|TAB460|TAB461|TAB464|TAB465|TAB467|TAB468)',
        'MedionTablet' => 'Android.*\bOYO\b|LIFE.*(P9212|P9514|P9516|S9512)|LIFETAB',
        'ArnovaTablet' => 'AN10G2|AN7bG3|AN7fG3|AN8G3|AN8cG3|AN7G3|AN9G3|AN7dG3|AN7dG3ST|AN7dG3ChildPad|AN10bG3|AN10bG3DT',
        // @reference: http://wiki.archosfans.com/index.php?title=Main_Page
        'ArchosTablet' => 'Android.*ARCHOS|\b101G9\b|\b80G9\b',
        // @reference: http://en.wikipedia.org/wiki/NOVO7
        'AinolTablet' => 'NOVO7|Novo7Aurora|Novo7Basic|NOVO7PALADIN',
        // @todo: inspect http://esupport.sony.com/US/p/select-system.pl?DIRECTOR=DRIVER
        // @ref: Readers http://www.atsuhiro-me.net/ebook/sony-reader/sony-reader-web-browser
        // @ref: http://www.sony.jp/support/tablet/
        'SonyTablet' => 'Sony.*Tablet|Xperia Tablet|Sony Tablet S|SO-03E|SGPT12|SGPT121|SGPT122|SGPT123|SGPT111|SGPT112|SGPT113|SGPT211|SGPT213|SGP311|SGP312|SGP321|EBRD1101|EBRD1102|EBRD1201',
        // @ref: db + http://www.cube-tablet.com/buy-products.html
        'CubeTablet' => 'Android.*(K8GT|U9GT|U10GT|U16GT|U17GT|U18GT|U19GT|U20GT|U23GT|U30GT)|CUBE U8GT',
        // @ref: http://www.cobyusa.com/?p=pcat&pcat_id=3001
        'CobyTablet' => 'MID1042|MID1045|MID1125|MID1126|MID7012|MID7014|MID7034|MID7035|MID7036|MID7042|MID7048|MID7127|MID8042|MID8048|MID8127|MID9042|MID9740|MID9742|MID7022|MID7010',
        // @ref: http://pdadb.net/index.php?m=pdalist&list=SMiT (NoName Chinese Tablets)
        // @ref: http://www.imp3.net/14/show.php?itemid=20454
        'SMiTTablet' => 'Android.*(\bMID\b|MID-560|MTV-T1200|MTV-PND531|MTV-P1101|MTV-PND530)',
        // @ref: http://www.rock-chips.com/index.php?do=prod&pid=2
        'RockChipTablet' => 'Android.*(RK2818|RK2808A|RK2918|RK3066)|RK2738|RK2808A',
        // @ref: http://www.telstra.com.au/home-phone/thub-2/
        'TelstraTablet' => 'T-Hub2',
        // @ref: http://www.fly-phone.com/devices/tablets/ ; http://www.fly-phone.com/service/
        'FlyTablet' => 'IQ310|Fly Vision',
        // @ref: http://www.bqreaders.com/gb/tablets-prices-sale.html
        'bqTablet' => 'bq.*(Elcano|Curie|Edison|Maxwell|Kepler|Pascal|Tesla|Hypatia|Platon|Newton|Livingstone|Cervantes|Avant)',
        // @ref: http://www.huaweidevice.com/worldwide/productFamily.do?method=index&directoryId=5011&treeId=3290
        // @ref: http://www.huaweidevice.com/worldwide/downloadCenter.do?method=index&directoryId=3372&treeId=0&tb=1&type=software (including legacy tablets)
        'HuaweiTablet' => 'MediaPad|IDEOS S7|S7-201c|S7-202u|S7-101|S7-103|S7-104|S7-105|S7-106|S7-201|S7-Slim',
        // Nec or Medias Tab
        'NecTablet' => '\bN-06D|\bN-08D',
        // Pantech Tablets: http://www.pantechusa.com/phones/
        'PantechTablet' => 'Pantech.*P4100',
        // Broncho Tablets: http://www.broncho.cn/ (hard to find)
        'BronchoTablet' => 'Broncho.*(N701|N708|N802|a710)',
        // @ref: http://versusuk.com/support.html
        'VersusTablet' => 'TOUCHPAD.*[78910]',
        // @ref: http://www.zync.in/index.php/our-products/tablet-phablets
        'ZyncTablet' => 'z1000|Z99 2G|z99|z930|z999|z990|z909|Z919|z900',
        // @ref: http://www.positivoinformatica.com.br/www/pessoal/tablet-ypy/
        'PositivoTablet' => 'TB07STA|TB10STA|TB07FTA|TB10FTA',
        // @ref: https://www.nabitablet.com/
        'NabiTablet' => 'Android.*\bNabi',
        'KoboTablet' => 'Kobo Touch|\bK080\b|\bVox\b Build|\bArc\b Build',
        // French Danew Tablets http://www.danew.com/produits-tablette.php
        'DanewTablet' => 'DSlide.*\b(700|701R|702|703R|704|802|970|971|972|973|974|1010|1012)\b',
        // Texet Tablets and Readers http://www.texet.ru/tablet/
        'TexetTablet' => 'NaviPad|TB-772A|TM-7045|TM-7055|TM-9750|TM-7016|TM-7024|TM-7026|TM-7041|TM-7043|TM-7047|TM-8041|TM-9741|TM-9747|TM-9748|TM-9751|TM-7022|TM-7021|TM-7020|TM-7011|TM-7010|TM-7023|TM-7025|TM-7037W|TM-7038W|TM-7027W|TM-9720|TM-9725|TM-9737W|TM-1020|TM-9738W|TM-9740|TM-9743W|TB-807A|TB-771A|TB-727A|TB-725A|TB-719A|TB-823A|TB-805A|TB-723A|TB-715A|TB-707A|TB-705A|TB-709A|TB-711A|TB-890HD|TB-880HD|TB-790HD|TB-780HD|TB-770HD|TB-721HD|TB-710HD|TB-434HD|TB-860HD|TB-840HD|TB-760HD|TB-750HD|TB-740HD|TB-730HD|TB-722HD|TB-720HD|TB-700HD|TB-500HD|TB-470HD|TB-431HD|TB-430HD|TB-506|TB-504|TB-446|TB-436|TB-416|TB-146SE|TB-126SE',
        // @note: Avoid detecting 'PLAYSTATION 3' as mobile.
        'PlaystationTablet' => 'Playstation.*(Portable|Vita)',
        // @ref: http://www.galapad.net/product.html
        'GalapadTablet' => 'Android.*\bG1\b',
        'GenericTablet' => 'Android.*\b97D\b|Tablet(?!.*PC)|ViewPad7|MID7015|BNTV250A|LogicPD Zoom2|\bA7EB\b|CatNova8|A1_07|CT704|CT1002|\bM721\b|hp-tablet|rk30sdk',
    );
 
    // List of mobile Operating Systems.
    protected static $operatingSystems = array(
        'AndroidOS' => 'Android',
        'BlackBerryOS' => 'blackberry|\bBB10\b|rim tablet os',
        'PalmOS' => 'PalmOS|avantgo|blazer|elaine|hiptop|palm|plucker|xiino',
        'SymbianOS' => 'Symbian|SymbOS|Series60|Series40|SYB-[0-9]+|\bS60\b',
        // @reference: http://en.wikipedia.org/wiki/Windows_Mobile
        'WindowsMobileOS' => 'Windows CE.*(PPC|Smartphone|Mobile|[0-9]{3}x[0-9]{3})|Window Mobile|Windows Phone [0-9.]+|WCE;',
        // @reference: http://en.wikipedia.org/wiki/Windows_Phone
        // http://wifeng.cn/?r=blog&a=view&id=106
        // http://nicksnettravels.builttoroam.com/post/2011/01/10/Bogus-Windows-Phone-7-User-Agent-String.aspx
        'WindowsPhoneOS' => 'Windows Phone OS|XBLWP7|ZuneWP7',
        'iOS' => '\biPhone.*Mobile|\biPod|\biPad',
        // http://en.wikipedia.org/wiki/MeeGo
        // @todo: research MeeGo in UAs
        'MeeGoOS' => 'MeeGo',
        // http://en.wikipedia.org/wiki/Maemo
        // @todo: research Maemo in UAs
        'MaemoOS' => 'Maemo',
        'JavaOS' => 'J2ME/|Java/|\bMIDP\b|\bCLDC\b',
        'webOS' => 'webOS|hpwOS',
        'badaOS' => '\bBada\b',
        'BREWOS' => 'BREW',
    );
 
    // List of mobile User Agents.
    protected static $userAgents = array(
        // @reference: https://developers.google.com/chrome/mobile/docs/user-agent
        'Chrome' => '\bCrMo\b|CriOS|Android.*Chrome/[.0-9]* (Mobile)?',
        'Dolfin' => '\bDolfin\b',
        'Opera' => 'Opera.*Mini|Opera.*Mobi|Android.*Opera|Mobile.*OPR/[0-9.]+',
        'Skyfire' => 'Skyfire',
        'IE' => 'IEMobile|MSIEMobile',
        'Firefox' => 'fennec|firefox.*maemo|(Mobile|Tablet).*Firefox|Firefox.*Mobile',
        'Bolt' => 'bolt',
        'TeaShark' => 'teashark',
        'Blazer' => 'Blazer',
        // @reference: http://developer.apple.com/library/safari/#documentation/AppleApplications/Reference/SafariWebContent/OptimizingforSafarioniPhone/OptimizingforSafarioniPhone.html#//apple_ref/doc/uid/TP40006517-SW3
        'Safari' => 'Version.*Mobile.*Safari|Safari.*Mobile',
        // @ref: http://en.wikipedia.org/wiki/Midori_(web_browser)
        //'Midori' => 'midori',
        'Tizen' => 'Tizen',
        'UCBrowser' => 'UC.*Browser|UCWEB',
        // @ref: https://github.com/serbanghita/Mobile-Detect/issues/7
        'DiigoBrowser' => 'DiigoBrowser',
        // http://www.puffinbrowser.com/index.php
        'Puffin' => 'Puffin',
        // @ref: http://mercury-browser.com/index.html
        'Mercury' => '\bMercury\b',
        // @reference: http://en.wikipedia.org/wiki/Minimo
        // http://en.wikipedia.org/wiki/Vision_Mobile_Browser
        'GenericBrowser' => 'NokiaBrowser|OviBrowser|OneBrowser|TwonkyBeamBrowser|SEMC.*Browser|FlyFlow|Minimo|NetFront|Novarra-Vision'
    );
 
    /**
     * Tests if the current request is an AJAX request by checking the X-Requested-With HTTP
     * request header that most popular JS frameworks now set for AJAX calls.
     *
     * @return  boolean
     */
    public static function is_ajax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }
 
    public static function is_json()
    {
        return (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
 
    public static function is_https()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? true : false ;
    }
   
    public static function is_post() {
        return arr::get($_SERVER, 'REQUEST_METHOD') === 'POST';
    }
 
    public static function is_get() {
        return arr::get($_SERVER, 'REQUEST_METHOD') === 'GET';
    }
   
    public static function is_mobile()
    {
        if ( self::$is_mobile !== null ) return self::$is_mobile ;
 
        $useragent = self::user_agent();
 
        if ( self::has_mobile_header() ) {
            self::$is_mobile = true ;
        }
 
        if ( self::$is_mobile === null ) {
            foreach ( self::$operatingSystems as $os => $regex ) {
                if ( self::match($regex, $useragent) ) {
                    self::$is_mobile = true ;
                    break ;
                }
            }
        }
 
        if ( self::$is_mobile === null ) {
            foreach ( self::$userAgents as $ua => $regex ) {
                if ( self::match($regex, $useragent) ) {
                    self::$is_mobile = true ;
                    break ;
                }
            }
        }
 
        if ( self::$is_mobile === null ) self::$is_mobile = false ;
 
        return self::$is_mobile ;
    }
 
    public static function is_phone()
    {
        if ( self::$is_phone !== null ) return self::$is_phone ;
 
        $useragent = self::user_agent();
 
        foreach ( self::$phoneDevices as $device => $regex ) {
            if ( self::match($regex, $useragent) ) {
                self::$is_mobile = true ;
                break ;
            }
        }
 
        if ( self::$is_phone === null ) self::$is_phone = false ;
 
        return self::$is_phone ;
    }
 
    public static function is_tablet()
    {
        if ( self::$is_tablet !== null ) return self::$is_tablet ;
 
        $useragent = self::user_agent();
 
        foreach ( self::$tabletDevices as $device => $regex ) {
            if ( self::match($regex, $useragent) ) {
                self::$is_tablet = true ;
                break ;
            }
        }
 
        if ( self::$is_tablet === null ) self::$is_tablet = false ;
 
        return self::$is_tablet ;
    }
 
    /**
     * Return http user agent
     */
    public static function user_agent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
    }
 
    public static function browser()
    {
        if ( self::$browser ) return self::$browser ;
 
        $user_agent = self::user_agent();
        $browsers = array('firefox', 'opera', 'chrome', 'safari',
            'mozilla', 'seamonkey', 'konqueror', 'netscape',
            'gecko', 'navigator', 'mosaic', 'lynx', 'amaya',
            'omniweb', 'avant', 'camino', 'flock', 'aol');
 
        // Test for IE (including 11)
        if ( stripos($user_agent, 'msie') !== false || stripos($user_agent, 'trident') ) {
            return self::$browser = 'msie';
        }
 
        foreach($browsers as $browser)
        {
            if (stripos($user_agent, $browser)) {
                return self::$browser = $browser ;
            }
        }
 
        return '' ;
    }
 
    public static function browser_version()
    {
        if ( self::$version ) return self::$version ;
 
        $browser = self::browser();
        $user_agent = self::user_agent();
 
        // Test for IE 11+
        if ( $browser == 'msie' && preg_match('/rv[\s:]?([0-9\.]*)/i', $user_agent, $match) ) {
            return self::$version = $match[1];
        }
 
        if ( preg_match('/'.$browser.'[\/ ]?([0-9\.]*)/i', $user_agent, $match) ) {
            return self::$version = $match[1];
        }
    }
 
    public static function browser_major_version()
    {
        $version = self::browser_version();
 
        $boom = explode('.', $version);
 
        return array_shift($boom);
    }
 
    public static function browser_engine()
    {
        if ( self::$engine ) return self::$engine ;
 
        $user_agent = self::user_agent();
 
        if ( stripos($user_agent, 'trident') ) {
            self::$engine = 'trident' ;
        }
        elseif ( stripos($user_agent, 'webkit')) {
            self::$engine = 'webkit' ;
        }
        elseif ( stripos($user_agent, 'presto')) {
            self::$engine = 'presto' ;
        }
        elseif ( stripos($user_agent, 'gecko')) {
            self::$engine = 'gecko' ;
        }
 
        return self::$engine ;
    }
 
    /**
     * Return if request was made by bots
     */
    public static function is_bot()
    {
        $user_agent = self::user_agent();
        $list = array('lastochka', 'bot', 'survey', 'spider', 'crawl', 'slurp', 'archiver', 'facebook', 'panscient', 'w3c_validator', 'jigsaw');
 
        if ( strlen(trim($user_agent)) < 25 ) return true ;
 
        foreach ( $list as $word ) {
            if ( stripos($user_agent, $word) !== false ) return true ;
        }
 
        return false ;
    }
 
    public static function has_mobile_header()
    {
        if(
            isset($_SERVER['HTTP_ACCEPT']) &&
            (strpos($_SERVER['HTTP_ACCEPT'], 'application/x-obml2d') !== false || // Opera Mini; @reference: http://dev.opera.com/articles/view/opera-binary-markup-language/
                strpos($_SERVER['HTTP_ACCEPT'], 'application/vnd.rim.html') !== false || // BlackBerry devices.
                strpos($_SERVER['HTTP_ACCEPT'], 'text/vnd.wap.wml') !== false ||
                strpos($_SERVER['HTTP_ACCEPT'], 'application/vnd.wap.xhtml+xml') !== false) ||
            isset($_SERVER['HTTP_X_WAP_PROFILE']) || // @todo: validate
            isset($_SERVER['HTTP_X_WAP_CLIENTID']) ||
            isset($_SERVER['HTTP_WAP_CONNECTION']) ||
            isset($_SERVER['HTTP_PROFILE']) ||
            isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) || // Reported by Nokia devices (eg. C3)
            isset($_SERVER['HTTP_X_NOKIA_IPADDRESS']) ||
            isset($_SERVER['HTTP_X_NOKIA_GATEWAY_ID']) ||
            isset($_SERVER['HTTP_X_ORANGE_ID']) ||
            isset($_SERVER['HTTP_X_VODAFONE_3GPDPCONTEXT']) ||
            isset($_SERVER['HTTP_X_HUAWEI_USERID']) ||
            isset($_SERVER['HTTP_UA_OS']) || // Reported by Windows Smartphones.
            isset($_SERVER['HTTP_X_MOBILE_GATEWAY']) || // Reported by Verizon, Vodafone proxy system.
            isset($_SERVER['HTTP_X_ATT_DEVICEID']) || // Seend this on HTC Sensation. @ref: SensationXE_Beats_Z715e
            //HTTP_X_NETWORK_TYPE = WIFI
            ( isset($_SERVER['HTTP_UA_CPU']) &&
                $_SERVER['HTTP_UA_CPU'] == 'ARM' // Seen this on a HTC.
            )
        ){
            return true;
        }
 
        return false;
    }
 
    protected static function match($regex, $subject)
    {
        // Escape the special character which is the delimiter.
        $regex = str_replace('/', '\/', $regex);
 
        return (bool) preg_match('/'.$regex.'/is', $subject);
 
    }
}