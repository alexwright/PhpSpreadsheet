<?php

namespace PhpOffice\PhpSpreadsheetTests\Functional;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class RowHeightTest extends AbstractFunctional
{
    public function providerFormats()
    {
        return [
            ['Xlsx'],
        ];
    }

    /**
     * @dataProvider providerFormats
     *
     * @param $format
     */
    public function testReadRowHeight($format): void
    {
        // create new sheet with column width
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello World !');
        $sheet->getRowDimension('1')->setRowHeight(20);
        $this->assertRow($spreadsheet);

        $reloadedSpreadsheet = $this->writeAndReload($spreadsheet, $format);
        $this->assertRow($reloadedSpreadsheet);
    }

    /**
     * @dataProvider providerFormats
     * 
     * @param $format
     */
    public function testReadRowHeightWithReadFilter($format): void
    {
        // Same test with a read filter removing a single row
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach (range(1, 5) as $columnIndex) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex) . '1', 'Hello World !');
        }

        // isFilteredRow iterates columnAttributes when calling the read filter
        $sheet->getColumnDimension('E')->setWidth(64);

        $sheet->getRowDimension('1')->setRowHeight(20);
        $this->assertRow($spreadsheet);

        // A reader-customeiser closure and ReadFilter implementation that skips column 'E'
        $readerCustomizer = function ($reader) {
            $readFilterStub = $this->createMock(IReadFilter::class);
            $readFilterStub->method('readCell')
                ->willReturnCallback(function ($column, $row, $worksheetName = '') {
                    return $column !== 'E';
                });
            $reader->setReadFilter($readFilterStub);
        };

        // Save and reload a filtered set and assert the same width
        $reloadedSpreadsheet = $this->writeAndReload($spreadsheet, $format, $readerCustomizer);
        $this->assertRow($reloadedSpreadsheet);
    }

    private function assertRow(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $rowDimensions = $sheet->getRowDimensions();

        self::assertArrayHasKey('1', $rowDimensions);
        $row = array_shift($rowDimensions);
        self::assertEquals(20, $row->getRowHeight());
    }
}
