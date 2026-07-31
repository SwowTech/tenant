<!doctype html
    public "-//w3c//dtd html 4.01 transitional//en" "http://www.w3c.org/tr/1999/rec-html401-19991224/loose.dtd">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI助手</title>
    <link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="../plugins/bootstrap/font-awesome.min.css?t=20230419" rel="stylesheet">
    <link href="../plugins/bootstrap/animate.min.css" rel="stylesheet">
    <link href="../plugins/bootstrap/style.min.css" rel="stylesheet">
    <link href="css/adminstyle.css" rel="stylesheet">
    <link href="../plugins/checkbox/checkbox.css" rel="stylesheet">
    <script src="../js/jquery.min.js"></script>
    <style>
        /* Markdown 基础样式 */
        .markdown-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            line-height: 1.7;
            color: #333;
            background-color: #fff;
        }

        /* 标题样式 */
        .markdown-container h1,
        .markdown-container h2,
        .markdown-container h3,
        .markdown-container h4,
        .markdown-container h5,
        .markdown-container h6 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #2d3748;
            line-height: 1.3;
        }

        .markdown-container h1 {
            font-size: 2rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }

        .markdown-container h2 {
            font-size: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }

        .markdown-container h3 {
            font-size: 1.25rem;
        }

        /* 段落样式 */
        .markdown-container p {
            margin-bottom: 1.2rem;
        }

        /* 链接样式 */
        .markdown-container a {
            color: #3182ce;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .markdown-container a:hover {
            color: #2c5282;
            text-decoration: underline;
        }

        .markdown-container .anchor-link {
            color: #cbd5e0;
            margin-left: 0.5rem;
        }

        .markdown-container h1:hover .anchor-link,
        .markdown-container h2:hover .anchor-link,
        .markdown-container h3:hover .anchor-link {
            color: #718096;
        }

        /* 列表样式 */
        .markdown-container ul,
        .markdown-container ol {
            margin-bottom: 1.2rem;
            padding-left: 1.8rem;
        }

        .markdown-container ul {
            list-style-type: disc;
        }

        .markdown-container ol {
            list-style-type: decimal;
        }

        .markdown-container li {
            margin-bottom: 0.5rem;
        }

        .markdown-container li p {
            margin-bottom: 0.5rem;
        }

        /* 任务列表 */
        .markdown-container .task-list {
            list-style-type: none;
            padding-left: 1.5rem;
        }

        .markdown-container .task-list li {
            position: relative;
            padding-left: 1.8rem;
        }

        .markdown-container .task-list input[type="checkbox"] {
            position: absolute;
            left: 0;
            top: 0.3rem;
            width: 1rem;
            height: 1rem;
            border-radius: 3px;
            background-color: #f7fafc;
            border: 1px solid #cbd5e0;
            cursor: default;
        }

        /* 代码块样式 */
        .markdown-container pre {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 6px;
            background-color: #f7fafc;
            overflow-x: auto;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }

        .markdown-container code {
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            background-color: #f7fafc;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 0.9rem;
        }

        .markdown-container pre code {
            padding: 0;
            background: transparent;
            font-size: 0.875rem;
        }

        /* 引用块样式 */
        .markdown-container blockquote {
            margin-bottom: 1.5rem;
            padding: 1rem 1.5rem;
            border-left: 4px solid #e2e8f0;
            background-color: #f7fafc;
            color: #718096;
            border-radius: 0 4px 4px 0;
        }

        .markdown-container blockquote p {
            margin-bottom: 0;
        }

        /* 表格样式 */
        .markdown-container .markdown-table {
            width: 100%;
            margin-bottom: 1.5rem;
            border-collapse: collapse;
            border-spacing: 0;
        }

        .markdown-container .markdown-table th,
        .markdown-container .markdown-table td {
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            text-align: left;
        }

        .markdown-container .markdown-table th {
            background-color: #f7fafc;
            font-weight: 600;
        }

        .markdown-container .markdown-table tr:nth-child(even) {
            background-color: #f7fafc;
        }

        .markdown-container .markdown-table .text-center {
            text-align: center;
        }

        .markdown-container .markdown-table .text-right {
            text-align: right;
        }

        /* 水平线样式 */
        .markdown-container hr {
            margin: 2rem 0;
            border: 0;
            border-top: 1px solid #e2e8f0;
        }

        /* 图片样式 */
        .markdown-container img {
            max-width: 100%;
            height: auto;
            margin: 1.5rem 0;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* 脚注样式 */
        .markdown-container .footnotes {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.9rem;
            color: #718096;
        }

        .markdown-container .footnotes ol {
            padding-left: 1.5rem;
        }

        .markdown-container .footnotes li {
            margin-bottom: 0.75rem;
        }

        .markdown-container .footnotes a {
            color: #718096;
        }

        .markdown-container .footnotes a:hover {
            color: #2d3748;
        }

        /* 响应式调整 */
        @media (max-width: 640px) {
            .markdown-container {
                padding: 1.5rem 1rem;
            }

            .markdown-container h1 {
                font-size: 1.75rem;
            }

            .markdown-container h2 {
                font-size: 1.4rem;
            }

            .markdown-container .markdown-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body class="gray-bg">
    <div class="markdown-container">
        {$markdown}
    </div>
</body>