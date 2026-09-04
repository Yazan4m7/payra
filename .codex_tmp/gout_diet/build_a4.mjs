import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const sourcePath = "C:\\Users\\Yazan\\Downloads\\GOut diet.xlsx";
const outputDir = "C:\\Users\\Yazan\\Desktop\\Projects\\GPTs\\jordan-payroll-hr\\outputs\\01a06ca4-22c8-70a1-80fe-ea0ce9a00c69";
const outputPath = `${outputDir}\\GOut diet - A4 readable.xlsx`;
const previewDir = "C:\\Users\\Yazan\\Desktop\\Projects\\GPTs\\jordan-payroll-hr\\.codex_tmp\\gout_diet\\previews_final";

const FONT = "Arial";
const COLORS = {
  ink: "#17212B",
  muted: "#4B5563",
  navy: "#23405A",
  bluePale: "#EAF2F8",
  green: "#245C3D",
  greenPale: "#E8F3EC",
  amber: "#8A4B08",
  amberPale: "#FFF3D6",
  red: "#8E2F2F",
  redPale: "#FBE9E7",
  deepRed: "#6D1F1F",
  grayPale: "#F4F6F8",
  line: "#CCD4DB",
  white: "#FFFFFF",
};

const sourceBlob = await FileBlob.load(sourcePath);
const sourceWorkbook = await SpreadsheetFile.importXlsx(sourceBlob);
const sourceSheet = sourceWorkbook.worksheets.getItem("Sheet1");
const sourceValues = sourceSheet.getRange("A1:D251").values;
if (sourceValues[0][3] !== "الأطعمة المسموح بها" || sourceValues[117][3] !== "اللحوم الحمراء:") {
  throw new Error("Unexpected source workbook structure; stopped before creating the print edition.");
}

const workbook = Workbook.create();

function setPageFrame(sheet, tabColor) {
  sheet.showGridLines = false;
  sheet.tabColor = tabColor;

  for (const col of ["A", "D"]) {
    sheet.getRange(`${col}1:${col}40`).format.columnWidth = 38;
  }
  for (const col of ["B", "E"]) {
    sheet.getRange(`${col}1:${col}40`).format.columnWidth = 3.2;
  }
  sheet.getRange("C1:C40").format.columnWidth = 3;

  const canvas = sheet.getRange("A1:E40");
  canvas.format.font = { name: FONT, size: 14, color: COLORS.ink };
  canvas.format.verticalAlignment = "center";
  canvas.format.wrapText = true;
}

function mergedRow(sheet, row, value, options = {}) {
  const range = sheet.getRange(`A${row}:E${row}`);
  range.merge();
  range.values = [[value]];
  range.format = {
    fill: options.fill ?? COLORS.white,
    font: {
      name: FONT,
      size: options.size ?? 14,
      bold: options.bold ?? false,
      italic: options.italic ?? false,
      color: options.color ?? COLORS.ink,
    },
    horizontalAlignment: options.align ?? "right",
    verticalAlignment: "center",
    wrapText: true,
    borders: options.border
      ? { preset: "outside", style: "thin", color: COLORS.line }
      : { preset: "none" },
    rowHeight: options.height ?? 22,
  };
  return range;
}

function titleBlock(sheet, title, subtitle) {
  mergedRow(sheet, 1, title, {
    size: 22,
    bold: true,
    color: COLORS.navy,
    height: 36,
  });
  mergedRow(sheet, 2, subtitle, {
    size: 12.5,
    italic: true,
    color: COLORS.muted,
    height: 27,
  });
  sheet.getRange("A3:E3").format.rowHeight = 9;
}

function fullWidthPanel(sheet, startRow, title, text, palette, height = 54) {
  mergedRow(sheet, startRow, title, {
    size: 17,
    bold: true,
    color: COLORS.white,
    fill: palette.heading,
    height: 28,
    border: true,
  });
  mergedRow(sheet, startRow + 1, text, {
    size: 15,
    fill: palette.body,
    height,
    border: true,
  });
}

function sideColumns(side) {
  return side === "right"
    ? { text: "D", bullet: "E", pair: "D:E" }
    : { text: "A", bullet: "B", pair: "A:B" };
}

function writeStack(sheet, side, sections, startRow = 4) {
  const cols = sideColumns(side);
  let row = startRow;

  for (let sectionIndex = 0; sectionIndex < sections.length; sectionIndex += 1) {
    const section = sections[sectionIndex];
    const header = sheet.getRange(`${cols.pair.split(":")[0]}${row}:${cols.pair.split(":")[1]}${row}`);
    header.merge();
    header.values = [[section.title]];
    header.format = {
      fill: section.heading,
      font: { name: FONT, size: 16, bold: true, color: COLORS.white },
      horizontalAlignment: "right",
      verticalAlignment: "center",
      wrapText: true,
      borders: { preset: "outside", style: "thin", color: section.heading },
      rowHeight: 28,
    };
    row += 1;

    for (let itemIndex = 0; itemIndex < section.items.length; itemIndex += 1) {
      const fill = itemIndex % 2 === 0 ? section.body : COLORS.white;
      const textCell = sheet.getRange(`${cols.text}${row}`);
      const bulletCell = sheet.getRange(`${cols.bullet}${row}`);
      textCell.values = [[section.items[itemIndex]]];
      bulletCell.values = [["•"]];
      textCell.format = {
        fill,
        font: { name: FONT, size: section.fontSize ?? 13.5, color: COLORS.ink },
        horizontalAlignment: "right",
        verticalAlignment: "center",
        wrapText: true,
        rowHeight: section.rowHeight ?? 20,
      };
      bulletCell.format = {
        fill,
        font: { name: FONT, size: 13, bold: true, color: section.heading },
        horizontalAlignment: "center",
        verticalAlignment: "center",
        rowHeight: section.rowHeight ?? 20,
      };
      row += 1;
    }

    if (sectionIndex < sections.length - 1) {
      sheet.getRange(`A${row}:E${row}`).format.rowHeight = 7;
      row += 1;
    }
  }

  return row;
}

function footer(sheet, row, pageNumber, note = "") {
  if (note) {
    mergedRow(sheet, row, note, {
      size: 11,
      italic: true,
      color: COLORS.muted,
      fill: COLORS.grayPale,
      height: 26,
      border: true,
    });
    row += 1;
  }
  mergedRow(sheet, row, `صفحة ${pageNumber} من 5 — للطباعة: A4 عمودي، وملاءمة ورقة واحدة في كل صفحة`, {
    size: 10,
    color: COLORS.muted,
    align: "center",
    height: 20,
  });
}

// Page 1: quick summary.
{
  const sheet = workbook.worksheets.add("1-الخلاصة");
  setPageFrame(sheet, COLORS.navy);
  titleBlock(sheet, "دليل غذائي مبسّط للنقرس", "نسخة كبيرة وواضحة من القائمة الأصلية، مرتبة حسب درجة الحذر");

  fullWidthPanel(
    sheet,
    4,
    "اختاري غالباً",
    "الماء، الخضروات، الفاكهة الكاملة، الحبوب الكاملة، الحليب واللبن والزبادي قليلة الدسم، البيض، العدس والفاصولياء والحمص والتوفو.",
    { heading: COLORS.green, body: COLORS.greenPale },
    60,
  );
  fullWidthPanel(
    sheet,
    7,
    "تناولي باعتدال",
    "الدجاج أو الديك الرومي المشوي، والأسماك العادية مثل السلمون أو القد أو التونة. اجعلي العصير والحلويات والجبن كامل الدسم كميات صغيرة وغير يومية.",
    { heading: COLORS.amber, body: COLORS.amberPale },
    66,
  );
  fullWidthPanel(
    sheet,
    10,
    "قللي كثيراً",
    "اللحوم الحمراء، القشريات وبعض الأسماك الأعلى بالبيورين، المشروبات المحلّاة، الأطعمة المقلية، المالحة، السريعة والمصنّعة.",
    { heading: COLORS.red, body: COLORS.redPale },
    60,
  );
  fullWidthPanel(
    sheet,
    13,
    "تجنبي أو اجعليها نادرة جداً",
    "أعضاء الحيوانات مثل الكبد والكلى والقلب. تجنبي الكحول أثناء نوبة النقرس، وخصوصاً البيرة، واتّبعي تعليمات الطبيب في غير ذلك.",
    { heading: COLORS.deepRed, body: COLORS.redPale },
    60,
  );

  mergedRow(sheet, 16, "مهم: الغذاء يساعد على تقليل النوبات، لكنه لا يحل محل الدواء الموصوف. لا تغيّري الدواء أو الجرعة من نفسك.", {
    size: 14.5,
    bold: true,
    color: COLORS.navy,
    fill: COLORS.bluePale,
    height: 48,
    border: true,
  });
  footer(sheet, 18, 1);
}

// Page 2: better everyday choices.
{
  const sheet = workbook.worksheets.add("2-اختيارات أفضل");
  setPageFrame(sheet, COLORS.green);
  titleBlock(sheet, "اختيارات أفضل في الأيام العادية", "اختاري الأنواع قليلة الدسم والملح، ووزعي الطعام على وجبات معتدلة");

  const rightEnd = writeStack(sheet, "right", [
    {
      title: "بروتينات نباتية وبيض ومنتجات حليب",
      heading: COLORS.green,
      body: COLORS.greenPale,
      items: [
        "البيض المسلوق أو المخفوق بقليل من الزيت",
        "الفاصولياء البيضاء",
        "الفاصولياء السوداء",
        "العدس",
        "الحمص",
        "الفول واللوبيا",
        "البازلاء",
        "التوفو",
        "فول الصويا",
        "الحليب الخالي أو القليل الدسم",
        "اللبن والزبادي قليل الدسم",
        "الزبادي اليوناني قليل الدسم",
        "الجبن القريش أو الكوتاج قليل الملح",
        "الريكوتا أو الموزاريلا قليلة الدسم",
        "مكسرات غير مملحة بكمية صغيرة",
      ],
    },
  ]);

  const leftEnd = writeStack(sheet, "left", [
    {
      title: "الحبوب الكاملة",
      heading: COLORS.green,
      body: COLORS.greenPale,
      items: [
        "الأرز البني",
        "الشوفان ودقيق الشوفان",
        "القمح الكامل",
        "الخبز الأسمر المصنوع من الحبوب الكاملة",
        "المعكرونة المصنوعة من القمح الكامل",
        "الشعير والشعير المجروش",
        "الذرة",
        "الأرز البري",
        "الكينوا",
      ],
    },
    {
      title: "دواجن وأسماك باعتدال",
      heading: COLORS.amber,
      body: COLORS.amberPale,
      items: [
        "الدجاج المشوي من دون جلد",
        "الديك الرومي المشوي",
        "السلمون أو القد أو التونة بكمية معتدلة",
        "بدّلي بين البروتين الحيواني والبقوليات",
      ],
    },
  ]);

  footer(sheet, Math.max(rightEnd, leftEnd) + 1, 2, "يفضل اختيار المنتجات الأقل ملحاً، وعدم اعتبار كلمة «قليل الدسم» إذناً بكمية غير محدودة.");
}

// Page 3: vegetables and whole fruit.
{
  const sheet = workbook.worksheets.add("3-خضار وفاكهة");
  setPageFrame(sheet, COLORS.green);
  titleBlock(sheet, "الخضروات والفاكهة الكاملة", "الفاكهة الكاملة أفضل من العصير. الماء هو المشروب الأساسي");

  const rightEnd = writeStack(sheet, "right", [
    {
      title: "الخضروات",
      heading: COLORS.green,
      body: COLORS.greenPale,
      items: [
        "البروكلي",
        "الجزر",
        "الخيار",
        "الطماطم",
        "الفلفل الأخضر",
        "البصل",
        "الثوم",
        "السبانخ",
        "اللفت",
        "القرنبيط",
        "البطاطا الحلوة",
        "الفطر",
        "القرع",
        "الخس",
        "الكرفس",
        "الباذنجان",
        "الفاصوليا الخضراء",
        "البازلاء الخضراء",
        "الكوسة",
        "الملفوف",
      ],
    },
  ]);

  const leftEnd = writeStack(sheet, "left", [
    {
      title: "الفاكهة الكاملة",
      heading: COLORS.green,
      body: COLORS.greenPale,
      items: [
        "التفاح",
        "الموز",
        "البرتقال",
        "الجريب فروت*",
        "التوت الأزرق والأحمر والبري",
        "الفراولة",
        "الكيوي",
        "الأناناس",
        "الرمان",
        "الخوخ والدراق",
        "المشمش",
        "العنب",
        "الكمثرى",
        "الأفوكادو",
        "التين",
        "البطيخ",
        "المانجو",
      ],
    },
    {
      title: "المشروبات",
      heading: COLORS.navy,
      body: COLORS.bluePale,
      items: [
        "الماء أولاً",
        "الشاي أو القهوة من دون سكر",
        "العصير الطبيعي: كمية صغيرة وليس بدلاً من الفاكهة",
      ],
    },
  ]);

  footer(sheet, Math.max(rightEnd, leftEnd) + 1, 3, "* إذا كانت تستخدم الكولشيسين: لا تشرب عصير الجريب فروت، واسألي الطبيب أو الصيدلي عن الجريب فروت.");
}

// Page 4: limit and avoid lists.
{
  const sheet = workbook.worksheets.add("4-قللي وتجنبي");
  setPageFrame(sheet, COLORS.red);
  titleBlock(sheet, "أطعمة تحتاج إلى تقليل أو تجنّب", "درجة الحذر تختلف حسب الحالة والأدوية؛ اتبعي تعليمات الطبيب أو أخصائي التغذية");

  const rightEnd = writeStack(sheet, "right", [
    {
      title: "قللي كثيراً من اللحوم الحمراء",
      heading: COLORS.red,
      body: COLORS.redPale,
      items: [
        "لحم البقر",
        "لحم الضأن أو الخروف",
        "لحم العجل",
        "لحم الغزال أو الوعل أو البيسون",
        "لحم الأرنب",
        "البط والإوز والسمان",
        "اللحم المفروم والمصنّع",
      ],
    },
    {
      title: "قللي المأكولات البحرية الأعلى بالبيورين",
      heading: COLORS.red,
      body: COLORS.redPale,
      items: [
        "الروبيان والقريدس",
        "الكركند والكابوريا",
        "المحار والرخويات",
        "السردين والأنشوجة",
        "السلمون المرقط",
        "السمك المالح أو المدخن أو المعلب",
      ],
    },
    {
      title: "قللي الحلويات والمشروبات المحلّاة",
      heading: COLORS.amber,
      body: COLORS.amberPale,
      items: [
        "الصودا والمشروبات السكرية",
        "العصائر المعبأة",
        "الكيك والبسكويت",
        "الآيس كريم والحلوى الشرقية",
        "الشوكولاتة والحلويات المصنّعة",
      ],
    },
  ]);

  const leftEnd = writeStack(sheet, "left", [
    {
      title: "تجنبي أعضاء الحيوانات",
      heading: COLORS.deepRed,
      body: COLORS.redPale,
      items: [
        "الكبد والكبدة",
        "الكلى",
        "القلب",
        "الدماغ",
        "الرئة",
        "المعدة والأمعاء",
      ],
    },
    {
      title: "الكحول",
      heading: COLORS.deepRed,
      body: COLORS.redPale,
      items: [
        "تجنبيه أثناء نوبة النقرس",
        "البيرة والبيرة الخفيفة وغير الكحولية",
        "النبيذ والمشروبات الروحية والكوكتيلات",
      ],
    },
    {
      title: "المقليات",
      heading: COLORS.red,
      body: COLORS.redPale,
      items: [
        "البطاطس والأرز والمعكرونة المقلية",
        "الدجاج والسمك والمأكولات البحرية المقلية",
        "الخضروات والساندويتشات المقلية",
      ],
    },
    {
      title: "المالح والسريع والمصنّع",
      heading: COLORS.amber,
      body: COLORS.amberPale,
      items: [
        "الوجبات السريعة ورقائق البطاطس",
        "المخللات والزيتون والمكسرات المالحة",
        "صلصة الصويا والمرق الجاهز",
        "اللحوم والمأكولات البحرية المعلبة أو المصنّعة",
      ],
    },
  ]);

  footer(sheet, Math.max(rightEnd, leftEnd) + 1, 4);
}

// Page 5: safety notes, provenance, and sources.
{
  const sheet = workbook.worksheets.add("5-ملاحظات ومصادر");
  setPageFrame(sheet, COLORS.navy);
  titleBlock(sheet, "ملاحظات مهمة قبل استخدام القائمة", "هذه ورقة مساعدة عامة، وليست خطة علاج شخصية");

  fullWidthPanel(
    sheet,
    4,
    "الأدوية",
    "لا تغيّري أي دواء أو جرعة. إذا كانت تستخدم الكولشيسين فلا تشرب عصير الجريب فروت، ويفضل سؤال الطبيب أو الصيدلي عن الأطعمة المتداخلة مع أدويتها.",
    { heading: COLORS.navy, body: COLORS.bluePale },
    58,
  );
  fullWidthPanel(
    sheet,
    7,
    "السوائل والحالات المزمنة",
    "لا تضعي هدفاً لزيادة الماء إذا كانت لديها أمراض كلى أو قلب أو تعليمات بتقييد السوائل. اتبعي الكمية التي يحددها الطبيب.",
    { heading: COLORS.navy, body: COLORS.bluePale },
    52,
  );
  fullWidthPanel(
    sheet,
    10,
    "العصير والمعلبات",
    "الفاكهة الكاملة أفضل من العصير. المعلبات ليست فئة واحدة: اختاري خضاراً قليلة الملح وفاكهة من دون شراب سكري، وقللي اللحوم والأسماك المعلبة والمصنّعة.",
    { heading: COLORS.amber, body: COLORS.amberPale },
    58,
  );
  fullWidthPanel(
    sheet,
    13,
    "ما تم ترتيبه",
    "تم حذف التكرار وتوحيد الأمثلة، ونقل اللحوم الحمراء وأعضاء الحيوانات والمأكولات البحرية والكحول والمقليات من منطقة «المسموح» إلى فئات أوضح للحذر.",
    { heading: COLORS.green, body: COLORS.greenPale },
    52,
  );

  mergedRow(sheet, 16, "المصدر الأولي: GOut diet.xlsx", {
    size: 11,
    bold: true,
    color: COLORS.navy,
    fill: COLORS.grayPale,
    height: 23,
  });
  mergedRow(sheet, 17, "مصادر طبية عامة:", {
    size: 12,
    bold: true,
    color: COLORS.navy,
    height: 23,
  });
  mergedRow(sheet, 18, "https://www.niams.nih.gov/health-topics/gout/diagnosis-treatment-and-steps-to-take", {
    size: 9.5,
    color: COLORS.navy,
    height: 26,
  });
  mergedRow(sheet, 19, "https://www.nice.org.uk/guidance/ng219/chapter/Recommendations", {
    size: 9.5,
    color: COLORS.navy,
    height: 23,
  });
  mergedRow(sheet, 20, "https://www.ouh.nhs.uk/media/btzd24ef/110778gout.pdf", {
    size: 9.5,
    color: COLORS.navy,
    height: 23,
  });
  mergedRow(sheet, 21, "https://www.nhs.uk/medicines/colchicine/", {
    size: 9.5,
    color: COLORS.navy,
    height: 23,
  });
  footer(sheet, 23, 5);
}

await fs.mkdir(outputDir, { recursive: true });
await fs.mkdir(previewDir, { recursive: true });

const sheetNames = [
  "1-الخلاصة",
  "2-اختيارات أفضل",
  "3-خضار وفاكهة",
  "4-قللي وتجنبي",
  "5-ملاحظات ومصادر",
];

for (const sheetName of sheetNames) {
  const check = await workbook.inspect({
    kind: "region",
    sheetId: sheetName,
    range: "A1:E40",
    include: "values,formulas",
    tableMaxRows: 40,
    tableMaxCols: 5,
    tableMaxCellChars: 220,
    maxChars: 16000,
  });
  console.log(`CHECK ${sheetName}`);
  console.log(check.ndjson);

  const preview = await workbook.render({
    sheetName,
    autoCrop: "all",
    scale: 1.6,
    format: "png",
  });
  const safeName = sheetName.replace(/[<>:"/\\|?*]/g, "_");
  await fs.writeFile(`${previewDir}\\${safeName}.png`, new Uint8Array(await preview.arrayBuffer()));
  console.log(`PREVIEW ${sheetName}: ${previewDir}\\${safeName}.png`);
}

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 300 },
  summary: "final formula error scan",
});
console.log("ERROR_SCAN");
console.log(errors.ndjson);

const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);
console.log(`OUTPUT ${outputPath}`);
