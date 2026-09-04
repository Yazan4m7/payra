import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const input = await FileBlob.load("C:\\Users\\Yazan\\Downloads\\GOut diet.xlsx");
const workbook = await SpreadsheetFile.importXlsx(input);
console.log(workbook.help("page setup and print area", {
  search: "page|print|paper|orientation|margin",
  include: "index,examples,notes",
  maxChars: 12000,
}).ndjson);
