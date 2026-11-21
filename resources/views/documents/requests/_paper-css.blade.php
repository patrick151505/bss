/* Document typography reset for the review preview — restore normal defaults
   inside the paper only, since the app's Tailwind Preflight zeroes p/heading
   margins and list bullets globally. Matches the editor + printed document. */
#review-paper p { margin: 0 0 5px 0; line-height: 1.4; }
#review-paper h1 { font-size: 2em;    font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
#review-paper h2 { font-size: 1.5em;  font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
#review-paper h3 { font-size: 1.17em; font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
#review-paper h4 { font-size: 1em;    font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
#review-paper h5 { font-size: 0.83em; font-weight: bold; margin: 0.4em 0; }
#review-paper h6 { font-size: 0.75em; font-weight: bold; margin: 0.4em 0; }
#review-paper ul { list-style: disc;    margin: 0 0 10px 0; padding-left: 40px; }
#review-paper ol { list-style: decimal; margin: 0 0 10px 0; padding-left: 40px; }
#review-paper li { margin: 0; }
#review-paper blockquote { margin: 0 0 10px 40px; }
#review-paper strong, #review-paper b { font-weight: bold; }
#review-paper em, #review-paper i { font-style: italic; }
#review-paper u { text-decoration: underline; }
#review-paper a { color: inherit; text-decoration: underline; }
#review-paper hr { border: 0; border-top: 1px solid #999; margin: 12px 0; }
#review-paper table { border-collapse: collapse; width: 100%; margin: 8px 0; }
#review-paper td, #review-paper th { border: 1px solid #d1d5db; padding: 3px 0px; vertical-align: top; }
#review-paper th { background: #f3f4f6; font-weight: 600; }
#review-paper table.no-border,
#review-paper table.no-border td,
#review-paper table.no-border th { border: none; background: transparent; }
#review-paper table.no-border td.border,
#review-paper table.no-border th.border,
#review-paper td.border,
#review-paper th.border { border-bottom: 1px solid #000; }
