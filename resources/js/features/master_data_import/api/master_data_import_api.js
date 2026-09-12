import apiClient from '../../../shared/api/api_client';

export const masterDataImportApi = {
    /**
     * Download template CSV file as Blob.
     *
     * @param {'products'|'categories'|'units'|'locations'} type
     */
    async downloadTemplate(type) {
        const response = await apiClient.get(`/master-data-import/${type}/template`, {
            responseType: 'blob',
        });
        return response;
    },

    /**
     * Upload and validate CSV file.
     *
     * @param {'products'|'categories'|'units'|'locations'} type
     * @param {File} file
     */
    async validateImport(type, file) {
        const formData = new FormData();
        formData.append('file', file);

        const response = await apiClient.post(`/master-data-import/${type}/validate`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        return response.data;
    },

    /**
     * Commit and import valid CSV file.
     *
     * @param {'products'|'categories'|'units'|'locations'} type
     * @param {File} file
     * @param {string} expectedSha256
     */
    async commitImport(type, file, expectedSha256) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('expected_sha256', expectedSha256);

        const response = await apiClient.post(`/master-data-import/${type}/commit`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        return response.data;
    },
};
