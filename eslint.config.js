import js from "@eslint/js";
import tsParser from "@typescript-eslint/parser";
import globals from "globals";
import vue from "eslint-plugin-vue";
import prettier from "eslint-config-prettier/flat";

export default [
    {
        ignores: ["node_modules/**", "public/build/**"],
    },
    js.configs.recommended,
    ...vue.configs["flat/recommended"],
    {
        files: ["resources/js/**/*.ts"],
        languageOptions: {
            globals: globals.browser,
            parser: tsParser,
            parserOptions: {
                ecmaVersion: "latest",
                sourceType: "module",
            },
        },
    },
    {
        files: ["resources/js/**/*.vue"],
        languageOptions: {
            globals: globals.browser,
            parserOptions: {
                ecmaVersion: "latest",
                parser: tsParser,
                sourceType: "module",
            },
        },
        rules: {
            "vue/html-indent": ["error", 4],
        },
    },
    prettier,
];
