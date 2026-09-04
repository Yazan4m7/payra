import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = "C:\\Users\\Yazan\\Downloads\\GOut diet.xlsx";
const previewDir = "C:\\Users\\Yazan\\Desktop\\Projects\\GPTs\\jordan-payroll-hr\\.codex_tmp\\gout_diet\\previews_source";

const input = await FileBlob.load(inputPath);
const workbook = await SpreadsheetFile.importXlsx(input);

const overview = await workbook.inspect({
  kind: "workbook,sheet,table,drawing,definedName",
  maxChars: 12000,
  tableMaxRows: 12,
  tableMaxCols: 12,
  tableMaxCellChars: 160,
});
console.log("OVERVIEW");
console.log(overview.ndjson);

const sheets = await workbook.inspect({
  kind: "sheet",
  include: "id,name",
  maxChars: 6000,
});
console.log("SHEETS");
console.log(sheets.ndjson);

await fs.mkdir(previewDir, { recursive: true });
const sheetRecords = sheets.ndjson
  .split(/\r?\n/)
  .filter(Boolean)
  .map((line) => {
    try { return JSON.parse(line); } catch { return null; }
  })
  .filter(Boolean);

const names = [...new Set(sheetRecords.map((record) => record.name).filter(Boolean))];
for (const name of names) {
  const details = await workbook.inspect({
    kind: "region,formula,computedStyle",
    sheetId: name,
    maxChars: 18000,
    tableMaxRows: 80,
    tableMaxCols: 20,
    tableMaxCellChars: 220,
    options: { maxResults: 300 },
  });
  console.log(`DETAILS ${name}`);
  console.log(details.ndjson);

  const safeName = name.replace(/[<>:"/\\|?*]/g, "_");
  const preview = await workbook.render({
    sheetName: name,
    autoCrop: "all",
    scale: 1.5,
    format: "png",
  });
  await fs.writeFile(`${previewDir}\\${safeName}.png`, new Uint8Array(await preview.arrayBuffer()));
  console.log(`PREVIEW ${name}: ${previewDir}\\${safeName}.png`);
}
