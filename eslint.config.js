import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

export default [
    {
        ignores: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'storage/**',
            'bootstrap/ssr/**',
        ],
    },

    js.configs.recommended,

    ...pluginVue.configs['flat/recommended'],

    {
        files: ['resources/js/**/*.{js,vue}'],
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.es2022,
            },
            ecmaVersion: 2022,
            sourceType: 'module',
        },
        rules: {
            // Vue-specific
            'vue/multi-word-component-names': 'off',
            'vue/require-default-prop': 'off',
            'vue/no-v-html': 'warn',

            // General
            'no-console': ['warn', { allow: ['warn', 'error'] }],
            'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
        },
    },
];
