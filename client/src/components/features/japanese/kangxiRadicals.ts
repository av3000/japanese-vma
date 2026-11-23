/**
 * The 214 Kangxi radicals in their standard order, so radical N is `KANGXI_RADICALS[N - 1]`. A
 * radical's number is also its id in the dictionary, so `/radical/N` is its page. Kanji payloads
 * name their radicals by number only (`radicals: ["75"]`); this turns the number into a glyph.
 */
export const KANGXI_RADICALS = Array.from(
	'一丨丶丿乙亅二亠人儿入八冂冖冫几凵刀力勹匕匚匸十卜卩厂厶又口囗土士夂夊夕大女子宀寸小尢尸屮山巛工己巾干幺广廴廾弋弓彐彡彳心戈戶手支攴文斗斤方无日曰月木欠止歹殳毋比毛氏气水火爪父爻爿片牙牛犬玄玉瓜瓦甘生用田疋疒癶白皮皿目矛矢石示禸禾穴立竹米糸缶网羊羽老而耒耳聿肉臣自至臼舌舛舟艮色艸虍虫血行衣襾見角言谷豆豕豸貝赤走足身車辛辰辵邑酉釆里金長門阜隶隹雨青非面革韋韭音頁風飛食首香馬骨高髟鬥鬯鬲鬼魚鳥鹵鹿麥麻黃黍黑黹黽鼎鼓鼠鼻齊齒龍龜龠',
);

/** The glyph of Kangxi radical `number` (1–214), or `null` outside that range. */
export const kangxiRadicalGlyph = (number: number): string | null =>
	Number.isInteger(number) && number >= 1 && number <= KANGXI_RADICALS.length ? KANGXI_RADICALS[number - 1] : null;
