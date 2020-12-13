<?php

namespace PhpOffice\PhpSpreadsheetTests\Functional;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ColumnWidthTest extends AbstractFunctional
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
    public function testReadColumnWidth($format): void
    {
        // create new sheet with column width
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello World !');
        $sheet->getColumnDimension('A')->setWidth(20);
        $this->assertColumn($spreadsheet);

        $reloadedSpreadsheet = $this->writeAndReload($spreadsheet, $format);
        $this->assertColumn($reloadedSpreadsheet);
    }

    /**
     * @dataProvider providerFormats
     *
     * @param $format
     */
    public function testReadColumnWidthWithReadFilter($format): void
    {
        // Same test with a read filter removing a single row
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach (range(1, 5) as $row) {
            $sheet->setCellValue("A{$row}", 'Hello World !');
        }

        // isFilteredColumn iterates rowAttributes when calling the read filter
        $sheet->getRowDimension(5)->setRowHeight(10);

        $sheet->getColumnDimension('A')->setWidth(20);
        $this->assertColumn($spreadsheet);

        // A reader-customeiser closure and ReadFilter implementation that skips rows >4
        $readerCustomizer = function ($reader) {
            $readFilterStub = $this->createMock(IReadFilter::class);
            $readFilterStub->method('readCell')
                ->willReturnCallback(function ($column, $row, $worksheetName = '') {
                    return $row <= 4;
                });
            $reader->setReadFilter($readFilterStub);
        };

        // Save and reload a filtered set and assert the same width
        $reloadedSpreadsheet = $this->writeAndReload($spreadsheet, $format, $readerCustomizer);
        $this->assertColumn($reloadedSpreadsheet);
    }

    private function assertColumn(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $columnDimensions = $sheet->getColumnDimensions();

        self::assertArrayHasKey('A', $columnDimensions);
        $column = array_shift($columnDimensions);
        self::assertEquals(20, $column->getWidth());
    }
}
