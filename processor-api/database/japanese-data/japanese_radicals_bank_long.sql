-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 04, 2020 at 08:47 PM
-- Server version: 10.4.10-MariaDB
-- PHP Version: 7.3.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jpdict_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `japanese_radicals_bank_long`
--

CREATE TABLE `japanese_radicals_bank_long` (
  `id` int(3) NOT NULL,
  `radical` varchar(13) DEFAULT NULL,
  `strokes` int(2) DEFAULT NULL,
  `meaning` varchar(17) DEFAULT NULL,
  `hiragana` varchar(22) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `japanese_radicals_bank_long`
--

INSERT INTO `japanese_radicals_bank_long` (`id`, `radical`, `strokes`, `meaning`, `hiragana`) VALUES
(1, '一', 1, 'one', 'いち / ichi'),
(2, '丨', 1, 'line', 'ぼう / bō'),
(3, '丶', 1, 'dot', 'てん / ten'),
(4, '丿', 1, 'slash', 'の / no'),
(5, '乙\n(乛、⺄、乚、乙、乀)', 1, 'second', 'おつ / otsu'),
(6, '亅', 1, 'hook', 'はねぼう / hanebō'),
(7, '二', 2, 'two', 'ふた / futa'),
(8, '亠', 2, 'lid', 'なべぶた / nabebuta'),
(9, '人\n(亻)', 2, 'man', 'ひと / hito'),
(10, '儿', 2, 'son, legs', 'にんにょう / ninnyō'),
(11, '入', 2, 'enter', 'いる / iru'),
(12, '八\n(丷)', 2, 'eight', 'はちがしら / hachigashira'),
(13, '冂', 2, 'wide', 'まきがまえ / makigamae'),
(14, '冖', 2, 'cloth cover', 'わかんむり / wakammuri'),
(15, '冫', 2, 'ice', 'にすい / nisui'),
(16, '几', 2, 'table', 'つくえ / tsukue'),
(17, '凵', 2, 'receptacle', 'うけばこ / ukebako'),
(18, '刀\n(刂、⺈)', 2, 'knife', 'かたな / katana'),
(19, '力', 2, 'power', 'ちから / chikara'),
(20, '勹', 2, 'wrap', 'つつみがまえ / tsutsumigamae'),
(21, '匕', 2, 'spoon', 'さじのひ / sajinohi'),
(22, '匚', 2, 'box', 'はこがまえ / hakogamae'),
(23, '匸', 2, 'hiding enclosure', 'かくしがまえ / kakushigamae'),
(24, '十', 2, 'ten', 'じゅう / jū'),
(25, '卜', 2, 'divination', 'ぼくのと / bokunoto'),
(26, '卩\n(㔾)', 2, 'seal (device)', 'ふしづくり / fushizukuri'),
(27, '厂', 2, 'cliff', 'がんだれ / gandare'),
(28, '厶', 2, 'private', 'む / mu'),
(29, '又', 2, 'again', 'また / mata'),
(30, '口', 3, 'mouth', 'くち / kuchi'),
(31, '囗', 3, 'enclosure', 'くにがまえ / kunigamae'),
(32, '土', 3, 'earth', 'つち / tsuchi'),
(33, '士', 3, 'scholar', 'さむらい / samurai'),
(34, '夂', 3, 'go', 'ふゆがしら / fuyugashira'),
(35, '夊', 3, 'go slowly', 'すいにょう / suinyō'),
(36, '夕', 3, 'evening', 'ゆうべ / yūbe'),
(37, '大', 3, 'big', 'だい / dai'),
(38, '女', 3, 'woman', 'おんな / onna'),
(39, '子', 3, 'child', 'こ / ko'),
(40, '宀', 3, 'roof', 'うかんむり / ukammuri'),
(41, '寸', 3, 'inch', 'すん / sun'),
(42, '小\n(⺌、⺍)', 3, 'small', 'しょう / shō'),
(43, '尢\n(尣)', 3, 'lame', 'まげあし / mageashi'),
(44, '尸', 3, 'corpse', 'しかばね / shikabane'),
(45, '屮', 3, 'sprout', 'てつ / tetsu'),
(46, '山', 3, 'mountain', 'やま / yama'),
(47, '巛\n(川)', 3, 'river', 'まがりがわ / magarigawa'),
(48, '工', 3, 'work', 'たくみ / takumi'),
(49, '己', 3, 'oneself', 'おのれ / onore'),
(50, '巾', 3, 'turban', 'はば / haba'),
(51, '干', 3, 'dry', 'ほす / hosu'),
(52, '幺\n(么)', 3, 'short thread', 'いとがしら / itogashira'),
(53, '广', 3, 'dotted cliff', 'まだれ / madare'),
(54, '廴', 3, 'long stride', 'えんにょう / ennyō'),
(55, '廾', 3, 'arch', 'にじゅうあし / nijūashi'),
(56, '弋', 3, 'shoot', 'しきがまえ / shikigamae'),
(57, '弓', 3, 'bow', 'ゆみ / yumi'),
(58, '彐\n(彑)', 3, 'snout', 'けいがしら / keigashira'),
(59, '彡', 3, 'bristle', 'さんづくり / sandzukuri'),
(60, '彳', 3, 'step', 'ぎょうにんべん / gyōnimben'),
(61, '心\n(忄、⺗)', 4, 'heart', 'りっしんべん / risshimben'),
(62, '戈', 4, 'halberd', 'かのほこ / kanohoko'),
(63, '戶\n(户、戸)', 4, 'door', 'と / to'),
(64, '手\n(扌、龵)', 4, 'hand', 'て / te'),
(65, '支', 4, 'branch', 'しにょう / shinyō'),
(66, '攴\n(攵)', 4, 'rap, tap', 'ぼくづくり / bokuzukuri'),
(67, '文', 4, 'script', 'ぶん / bun'),
(68, '斗', 4, 'dipper', 'とます / tomasu'),
(69, '斤', 4, 'axe', 'おの / ono'),
(70, '方', 4, 'square', 'ほう / hō'),
(71, '无\n(旡)', 4, 'not', 'なし / nashi'),
(72, '日', 4, 'sun', 'にち / nichi'),
(73, '曰', 4, 'say', 'いわく / iwaku'),
(74, '月', 4, 'moon', 'つき / tsuki'),
(75, '木', 4, 'tree', 'き / ki'),
(76, '欠', 4, 'lack', 'あくび / akubi'),
(77, '止', 4, 'stop', 'とめる / tomeru'),
(78, '歹\n(歺)', 4, 'death', 'がつ / gatsu'),
(79, '殳', 4, 'weapon', 'ほこつくり / hokotsukuri'),
(80, '毋\n(母)', 4, 'do not', 'なかれ / nakare'),
(81, '比', 4, 'compare', 'くらべる / kuraberu'),
(82, '毛', 4, 'fur', 'け / ke'),
(83, '氏', 4, 'clan', 'うじ / uji'),
(84, '气', 4, 'steam', 'きがまえ / kigamae'),
(85, '水\n(氵、氺)', 4, 'water', 'みず / mizu'),
(86, '火\n(灬)', 4, 'fire', 'ひ / hi'),
(87, '爪\n(爫)', 4, 'claw', 'つめ / tsume'),
(88, '父', 4, 'father', 'ちち / chichi'),
(89, '爻', 4, 'Trigrams', 'こう / kō'),
(90, '爿\n(丬)', 4, 'split wood', 'しょうへん / shōhen'),
(91, '片', 4, 'slice', 'かた / kata'),
(92, '牙', 4, 'fang', 'きば / kiba'),
(93, '牛\n(牜、⺧)', 4, 'cow', 'うし / ushi'),
(94, '犬\n(犭)', 4, 'dog', 'いぬ / inu'),
(95, '玄', 5, 'profound', 'げん / gen'),
(96, '玉\n(王、玊)', 5, 'jade', 'たま / tama'),
(97, '瓜', 5, 'melon', 'うり / uri'),
(98, '瓦', 5, 'tile', 'かわら / kawara'),
(99, '甘', 5, 'sweet', 'あまい / amai'),
(100, '生', 5, 'life', 'うまれる / umareru'),
(101, '用', 5, 'use', 'もちいる / mochiiru'),
(102, '田', 5, 'field', 'た / ta'),
(103, '疋\n(⺪)', 5, 'bolt of cloth', 'ひき / hiki'),
(104, '疒', 5, 'sickness', 'やまいだれ / yamaidare'),
(105, '癶', 5, 'footsteps', 'はつがしら / hatsugashira'),
(106, '白', 5, 'white', 'しろ / shiro'),
(107, '皮', 5, 'skin', 'けがわ / kegawa'),
(108, '皿', 5, 'dish', 'さら / sara'),
(109, '目\n(⺫)', 5, 'eye', 'め / me'),
(110, '矛', 5, 'spear', 'むのほこ / munohoko'),
(111, '矢', 5, 'arrow', 'や / ya'),
(112, '石', 5, 'stone', 'いし / ishi'),
(113, '示\n(礻)', 5, 'spirit', 'しめす / shimesu'),
(114, '禸', 5, 'track', 'ぐうのあし / gūnoashi'),
(115, '禾', 5, 'grain', 'のぎ / nogi'),
(116, '穴', 5, 'cave', 'あな / ana'),
(117, '立', 5, 'stand', 'たつ / tatsu'),
(118, '竹\n(⺮)', 6, 'bamboo', 'たけ / take'),
(119, '米', 6, 'rice', 'こめ / kome'),
(120, '糸\n(糹)', 6, 'silk', 'いと / ito'),
(121, '缶', 6, 'jar', 'ほとぎ / hotogi'),
(122, '网\n(⺲、罓、⺳)', 6, 'net', 'あみがしら / amigashira'),
(123, '羊\n(⺶、⺷)', 6, 'sheep', 'ひつじ / hitsuji'),
(124, '羽', 6, 'feather', 'はね / hane'),
(125, '老\n(耂)', 6, 'old', 'おい / oi'),
(126, '而', 6, 'and', 'しかして / shikashite'),
(127, '耒', 6, 'plow', 'らいすき / raisuki'),
(128, '耳', 6, 'ear', 'みみ / mimi'),
(129, '聿\n(⺺、⺻)', 6, 'brush', 'ふでづくり / fudezukuri'),
(130, '肉\n(⺼)', 6, 'meat', 'にく / niku'),
(131, '臣', 6, 'minister', 'しん / shin'),
(132, '自', 6, 'self', 'みずから / mizukara'),
(133, '至', 6, 'arrive', 'いたる / itaru'),
(134, '臼', 6, 'mortar', 'うす / usu'),
(135, '舌', 6, 'tongue', 'した / shita'),
(136, '舛', 6, 'oppose', 'ます / masu'),
(137, '舟', 6, 'boat', 'ふね / fune'),
(138, '艮', 6, 'stopping', 'こん / kon'),
(139, '色', 6, 'color', 'いろ / iro'),
(140, '艸\n(⺿)', 6, 'grass', 'くさ / kusa'),
(141, '虍', 6, 'tiger', 'とらかんむり / torakammuri'),
(142, '虫', 6, 'insect', 'むし / mushi'),
(143, '血', 6, 'blood', 'ち / chi'),
(144, '行', 6, 'walk enclosure', 'ぎょう / gyō'),
(145, '衣\n(⻂)', 6, 'clothes', 'ころも / koromo'),
(146, '襾\n(西、覀)', 6, 'cover', 'ア / a'),
(147, '見', 7, 'see', 'みる / miru'),
(148, '角\n(⻇)', 7, 'horn', 'つの / tsuno'),
(149, '言\n(訁)', 7, 'speech', 'ことば / kotoba'),
(150, '谷', 7, 'valley', 'たに / tani'),
(151, '豆', 7, 'bean', 'まめ / mame'),
(152, '豕', 7, 'pig', 'いのこ / inoko'),
(153, '豸', 7, 'badger', 'むじな / mujina'),
(154, '貝', 7, 'shell', 'かい / kai'),
(155, '赤', 7, 'red', 'あか / aka'),
(156, '走', 7, 'run', 'はしる / hashiru'),
(157, '足\n(⻊)', 7, 'foot', 'あし / ashi'),
(158, '身', 7, 'body', 'み / mi'),
(159, '車', 7, 'cart', 'くるま / kuruma'),
(160, '辛', 7, 'bitter', 'からい / karai'),
(161, '辰', 7, 'morning', 'しんのたつ / shinnotatsu'),
(162, '辵\n(⻌、⻍、⻎)', 7, 'walk', 'しんにょう / shinnyō'),
(163, '邑\n(⻏)', 7, 'city', 'むら / mura'),
(164, '酉', 7, 'wine', 'ひよみのとり / hyominotori'),
(165, '釆', 7, 'distinguish', 'のごめ / nogome'),
(166, '里', 7, 'village', 'さと / sato'),
(167, '金\n(釒)', 8, 'gold', 'かね / kane'),
(168, '長\n(镸)', 8, 'long', 'ながい / nagai'),
(169, '門', 8, 'gate', 'もん / mon'),
(170, '阜\n(⻖)', 8, 'mound', 'おか / oka'),
(171, '隶', 8, 'slave', 'れいづくり / reizukuri'),
(172, '隹', 8, 'short-tailed bird', 'ふるとり / furutori'),
(173, '雨', 8, 'rain', 'あめ / ame'),
(174, '青\n(靑)', 8, 'blue', 'あお / ao'),
(175, '非', 8, 'wrong', 'あらず / arazu'),
(176, '面\n(靣)', 9, 'face', 'めん / men'),
(177, '革', 9, 'leather', 'かくのかわ / kakunokawa'),
(178, '韋', 9, 'tanned leather', 'なめしがわ / nameshigawa'),
(179, '韭', 9, 'leek', 'にら / nira'),
(180, '音', 9, 'sound', 'おと / oto'),
(181, '頁', 9, 'leaf', 'おおがい / ōgai'),
(182, '風', 9, 'wind', 'かぜ / kaze'),
(183, '飛', 9, 'fly', 'とぶ / tobu'),
(184, '食\n(飠)', 9, 'eat', 'しょく / shoku'),
(185, '首', 9, 'head', 'くび / kubi'),
(186, '香', 9, 'fragrant', 'においこう / nioikō'),
(187, '馬', 10, 'horse', 'うま / uma'),
(188, '骨', 10, 'bone', 'ほね / hone'),
(189, '高\n(髙)', 10, 'tall', 'たかい / takai'),
(190, '髟', 10, 'hair', 'かみがしら / kamigashira'),
(191, '鬥', 10, 'fight', 'とうがまえ / tōgamae'),
(192, '鬯', 10, 'sacrificial wine', 'ちょう / chō'),
(193, '鬲', 10, 'cauldron', 'かなえ / kanae'),
(194, '鬼', 10, 'ghost', 'おに / oni'),
(195, '魚', 11, 'fish', 'うお / uo'),
(196, '鳥', 11, 'bird', 'とり / tori'),
(197, '鹵', 11, 'salt', 'ろ / ro'),
(198, '鹿', 11, 'deer', 'しか / shika'),
(199, '麥', 11, 'wheat', 'むぎ / mugi'),
(200, '麻', 11, 'hemp', 'あさ / asa'),
(201, '黃', 12, 'yellow', 'きいろ / kiiro'),
(202, '黍', 12, 'millet', 'きび / kibi'),
(203, '黑', 12, 'black', 'くろ / kuro'),
(204, '黹', 12, 'embroidery', 'ふつ / futsu'),
(205, '黽', 13, 'frog', 'べん / ben'),
(206, '鼎', 13, 'tripod', 'かなえ / kanae'),
(207, '鼓', 13, 'drum', 'つづみ / tsudzumi'),
(208, '鼠', 13, 'rat', 'ねずみ / nezumi'),
(209, '鼻', 14, 'nose', 'はな / hana'),
(210, '齊\n(斉)', 14, 'even', 'せい / sei'),
(211, '齒', 15, 'tooth', 'は / ha'),
(212, '龍', 16, 'dragon', 'りゅう / ryū'),
(213, '龜', 16, 'turtle', 'かめ / kame'),
(214, '龠', 17, 'flute', 'やく / yaku');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `japanese_radicals_bank_long`
--
ALTER TABLE `japanese_radicals_bank_long`
  ADD PRIMARY KEY (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
