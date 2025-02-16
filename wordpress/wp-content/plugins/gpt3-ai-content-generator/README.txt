===AI Power: Complete AI Pack===
Contributors: senols
Tags: ai, chat, deepseek, openai, chatgpt
Requires at least: 5.0.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.9.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Chatbot, Content Writer, Auto Content Writer, Product Writer, Image Generator, Audio Converter, AI Training, Embeddings and more.
 
== Description ==

AI Power is an all-in-one AI solution for WordPress, featuring models like OpenAI's o1, o3, GPT-4, GPT-3.5, Assistant API, along with DeepSeek, Claude, Gemini, Azure, Grok, Mistral, LLaMA, Yi Chat, and Alpaca.

It uses o1-mini, o1-preview, GPT-3.5, GPT-4,  GPT-4o, GPT-4o-mini, GPT-4 Vision, Gemini 1.5 Flash, Anthropic (Claude 3 Sonnet, Opus etc.), DeepSeek and more to generate content, images, and forms with customizable options. It includes AI training, Chat widget, WooCommerce integration, Embeddings and more.

Please read documentation here: [https://docs.aipower.org/](https://docs.aipower.org/)

== Core Features ==
* Multiple AI providers (OpenAI, Microsoft Azure, Google and OpenRouter)
* Latest AI models (GPT-4, Claude, Gemini, Llama, Grok, DeepSeek and more)
* OpenAI Assistant API
* Chatbot
* Content writer
* Bulk writer
- WooCommerce product writer
* Image Generator (DALL-E, Stable Diffusion 🚀🚀🚀, Flux and more)
* PDF Chat
* AI Assistant (Integrated with Gutenberg and Classic Editor)
* AI Training (Pinecone and Qdrant integrated)
* Fine tuning
* Audio Converter
* Embeddings
* AI Forms - Design your own forms and embed them into your website
* Playground
* SEO Optimizer
* Semantic search with Embeddings
* Pexels, Pixabay, Replicate integrated
* Scheduled Posts
* Speech-to-Post (Whisper)
* Text-to-Speech (ElevenLabs)
* Text-to-Speech (Google)
* Role Manager
* Token management
* Comment Replier
* Twitter bot

== Integrations ==

- OpenAI: Use GPT models, Whisper and text to speech for advanced AI capabilities. Supports OpenAI Assistant API too.
- OpenRouter: All models on OpenRouter are accesible via our plugin.
- Azure OpenAI: If you dont have OpenAI API access, you can use Azure.
- Google: Gemini Pro, Gemini 1.0, Gemini 1.5 Flash and Gemini 1.5 Pro langauge models.
- Open Source LLMs: Mistral, LLaMA, Yi Chat, Alpaca.
- SEO Tools: Optimize your content with Yoast SEO, All In One SEO, Rank Math and The SEO Framework.
- Image Libraries: Enhance your visuals with Pexels and Pixabay integration.
- Image Generators: Create unique images using DALL-E and Stable Diffusion.
- Vector Databases: Build engaging content and implement long-term external memory for chatbots.
- E-commerce: Improve product descriptions with WooCommerce integration.
- Google Sheets: Streamline data management and organization.
- RSS Feeds: Stay updated with the latest content from your favorite sources.
- Text-to-Speech: Convert your text into lifelike speech with ElevenLabs, Google and OpenAI Text-to-Speech integrations.

== Installation ==
 
1. Upload `gpt3-ai-content-generator.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Setup OpenAI API key.
4. Setup temperature, max tokens, best of, frequenct penalty.
5. Go to content writer.
6. Enter title, for example: Tesla Electric cars.
7. Enter number of headings, for example: 7
8. Click generate.
9. Save.

== Screenshots ==

1. Settings
2. Classic Editor
3. Block Editor
4. Benchmark Results
 
Note: You can view non-Minified JS files [here](https://github.com/aipowerorg/Non-Minified-JS-Files).

== Changelog ==

= 1.9.8 =

- Fixed an issue in the chat bot.

= 1.9.7 =

- Added **Internet Browsing** for AI Forms.

[Learn more here](https://docs.aipower.org/docs/ai-forms#internet-browsing).

= 1.9.6 =

- Fixed chat bot fullscreen issue.

= 1.9.5 =

- Added KaTeX support for AI Forms, enabling mathematical expressions. (Works only when the form output is set to Inline.)

= 1.9.4 =

- Added a conversation sidebar for the chatbot (shortcode only).
- Fixed an issue where Google was not working in AI forms.
- Improved copy functionality in AI forms.
- Improved markdown formatting for headings in chat responses.

= 1.9.3 =

- Added doc, docx support for AI Forms file upload tool.

= 1.9.2 =

Added Provider selection in AI Forms.

= 1.9.1 =

- o3-mini added.
- File upload option for AI forms. For now it supports text and csv only.
- Bug fixes in PromptBase

= 1.9.0 =

New AI Forms.

= 1.8.99 =

Improved token management page.

= 1.8.98 =

Better markdown support for AI Forms.

= 1.8.97 =

This release includes several improvements and security patches.

- Added OpenAI o1 model support.
- Fixed issues with Google Voice selection in the chatbot.
- Fixed issues related to deleting PDFs from the Pinecone namespace.
- Improved the "Delete All PDFs" functionality.
- Added the ability to upload data directly to the vector database through chatbot settings.
- AutoGPT - RSS module now retrieves descriptions from target RSS feeds. You can use the [description] variable in custom prompts to provide the AI with more context.

= 1.8.96 =

- Implemented IP anonymization in chat logs to ensure GDPR compliance.
- Markdown improvements in the chat bot.
- New safety category for Google models.
- Improved display of leads and feedback in chat logs.

= 1.8.95 =

This release includes couple of improvements for the chat bot:

- **OpenAI Assistant API Integration**: The chatbot now supports the OpenAI Assistant API. To use it, go to your chatbot settings and click the "Sync" button next to the models. This will synchronize your assistants. 

[Learn more here](https://docs.aipower.org/docs/Chatbot/ai-settings#selecting-assistant).

- **Improved Markdown Formatting**: Switched to a better third-party library for parsing markdown.
- **Simplified Interface**: I've removed the AI Name and Avatar options to keep the chat window clean and simple.

The next release will introduce a conversation sidebar for viewing and managing conversations.

**Note:** If you're using a caching plugin, please remember to clear your site cache to make sure that the changes take effect.

= 1.8.94 =

This update imrproves image generation capabilities in both Express Mode and AutoGPT.

- **Custom Prompts for Image Generation**: You can now set your own custom prompts. Go to the **Dashboard > Tools** tab to configure your prompt or choose from ready-to-use templates.
- **Improved Replicate Integration**: Improved support for configuring individual models (e.g., Flux, Flux Ultra) with detailed settings. Go to **Dashboard > Tools** and click the **Settings** icon for Replicate to customize each model.

= 1.8.93 =

Fixed compatibility issues with OpenRouter integration following their API update today. Adjustments were made to align with their new API structure.

= 1.8.92 =

This release includes several improvements.

= 1.8.91 =

- Updated third-party libraries.
- Added WordPress 6.7 compatibility
- Security fixes.

= 1.8.90 =

This release includes several improvements and security patches.

= 1.8.89 =

- New moderation model added: omni-moderation-latest
- Fixed issues with widget preview display.
- Fixed widget toggle functionality.
- Fixed chatbot duplication.

= 1.8.88 =

- Updated third-party libraries to the latest versions.
- Fixed an issue with OpenRouter streaming.
- Fixed console error occurrences.
- Removed unused functions for optimization.

= 1.8.87 =

- Lead Collection added for chat bot. You can now collect leads and view them under the Logs tab.

= 1.8.86 =

- Few tweaks to the new dashboard.
- Fixed log saving issue.
- Added token/cost details.
- Tweaked o1 model handling.

= 1.8.85 =

- Few tweaks to the new dashboard.
- Added Export, Import, and Duplicate options for chatbots.
- Fixed issue where instructions were sent with chat messages.

= 1.8.84 =

- New UI. This is a major update. Please make sure to clear your entire cache.

= 1.8.83 =

- New UI. This is a major update. Please make sure to clear your entire cache.

= 1.8.82 =

- New UI. This is a major update. Please make sure to clear your entire cache.
- Fixed AI Forms new line issue.

= 1.8.81 =

- Performance improvements in scheduled jobs.

= 1.8.80 =

- Improved memory management for the chatbot, allowing higher memory limits for longer conversation history.
- Fixed an issue where the bot occasionally failed to remember previous conversations.
- Now we are sending user queries via form submission instead of the URL, removing URL length limitations in the chat bot while using streaming.
- Fixed encoding issues for non-Latin languages like Ukrainian and Russian.
- Export logs now verify proper permissions for the uploads folder.
- Improved handling of the o1 model by bypassing unsupported parameters such as top_p, frequency and presence penalties, and streaming. Check o1 models limitations [here](https://docs.aipower.org/docs/ai-engine/openai/gpt-models#o1-models-beta).

= 1.8.79 =

- Added support for the o1-preview and o1-mini models. Please note that access to these models requires your organization to be at usage tier 5 or higher. See [usage tiers](https://platform.openai.com/docs/guides/rate-limits/usage-tiers) for more details. If you are accessing o1-preview or o1-mini via OpenRouter, make sure to disable streaming.
- Users can now enable or disable audio directly from the chat interface. More information [here](https://docs.aipower.org/docs/ChatGPT/advanced-setup/voice-chat).
- Fixed a spacing issue in AI Form outputs for consistent formatting.
- Updated chat logs to use current_time('timestamp') instead of time() for more accurate local time records.
- Added a "Limits" button to check OpenRouter usage and credits.

= 1.8.78 =

- Added new "Copy" and "Feedback" features for the chatbot. For more details, refer to the [documentation](https://docs.aipower.org/docs/ChatGPT/advanced-setup/feedback-collection)
- Optimized the log retrieval process in the Chat Log tab for better performance.
- Added the ability to block IP addresses directly from the log table.
- Fixed hyperlink, markdown and some formatting issues in the chat bot.
- Added an "Empty Log Table" option under Chat Settings - Performance tab to quickly clear the log table when it grows too large.
- Improved bulk user retrieval, now excluding subscribers for AutoGPT.
- SDK update.

**Note:** If you're using a caching plugin, please remember to clear your site cache to ensure that the changes take effect.

= 1.8.77 =

[New]

- Added support for new image models: Flux, SDXL, Kandinsky, and more for text-to-image generation across Content Writer, AutoGPT, and Image Generator modules.

For more details, refer to the [documentation](https://docs.aipower.org/docs/content-writer/express-mode/images#replicate)

[Improvements]

- Better markdown support for chat bot.
- Fixed broken links in chat response.

**Note:** If you're using a caching plugin, please remember to clear your site cache to ensure that the changes take effect.