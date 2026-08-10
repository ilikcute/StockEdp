<?php

namespace App\Features\MasterDataImport\Services;

use App\Features\MasterDataImport\Enums\MasterDataImportType;

class MasterDataImportTemplateService
{
    /**
     * Generate template CSV content with UTF-8 BOM.
     */
    public function generateTemplate(MasterDataImportType $type): string
    {
        $headers = $type->canonicalHeaders();
        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);

        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $headers);
        rewind($fp);
        $csvContent = stream_get_contents($fp);
        fclose($fp);

        return $bom.$csvContent;
    }
}
