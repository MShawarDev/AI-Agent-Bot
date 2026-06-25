import Alpine from 'alpinejs';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

window.Alpine = Alpine;

// Configure marked for safe inline rendering
marked.setOptions({ breaks: true });

window.renderMarkdown = (text) => DOMPurify.sanitize(marked.parse(text ?? ''));

Alpine.start();
