import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const input = await FileBlob.load("C:\\Users\\Yazan\\Downloads\\GOut diet.xlsx");
const workbook = await SpreadsheetFile.importXlsx(input);
const sheet = workbook.worksheets.getItem("Sheet1");
const values = sheet.getRange("A1:D251").values;
for (let index = 0; index < values.length; index += 1) {
  if (values[index].some((value) => value !== null && value !== "")) {
    console.log(JSON.stringify({ row: index + 1, values: values[index] }, null, 0));
  }
}
