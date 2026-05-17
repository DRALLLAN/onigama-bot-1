import os
import logging
import httpx
from telegram import Update
from telegram.ext import (
    ApplicationBuilder,
    CommandHandler,
    MessageHandler,
    filters,
    ContextTypes,
)

# ─── Logging ────────────────────────────────────────────────────────────────
logging.basicConfig(
    format="%(asctime)s | %(levelname)s | %(name)s | %(message)s",
    level=logging.INFO,
)
logger = logging.getLogger(__name__)

# ─── Config from environment ─────────────────────────────────────────────────
TELEGRAM_TOKEN = os.environ["TELEGRAM_TOKEN"]
OPENROUTER_API_KEY = os.environ["OPENROUTER_API_KEY"]
MODEL = os.getenv("AI_MODEL", "mistralai/mistral-7b-instruct:free")
OPENROUTER_URL = "https://openrouter.ai/api/v1/chat/completions"

HEADERS = {
    "Authorization": f"Bearer {OPENROUTER_API_KEY}",
    "Content-Type": "application/json",
    "HTTP-Referer": "https://t.me/onigama_ai_bot",
}


# ─── Helper: call OpenRouter ─────────────────────────────────────────────────
async def call_ai(system_prompt: str, user_message: str, max_tokens: int = 700) -> str:
    payload = {
        "model": MODEL,
        "max_tokens": max_tokens,
        "temperature": 0.7,
        "messages": [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_message},
        ],
    }
    try:
        async with httpx.AsyncClient(timeout=30) as client:
            response = await client.post(OPENROUTER_URL, headers=HEADERS, json=payload)
            response.raise_for_status()
            data = response.json()
            return data["choices"][0]["message"]["content"].strip()
    except httpx.TimeoutException:
        logger.error("OpenRouter request timed out")
        return "⏳ پاسخ دریافت نشد (timeout). لطفاً دوباره تلاش کن."
    except httpx.HTTPStatusError as e:
        logger.error(f"HTTP error {e.response.status_code}: {e.response.text}")
        return f"⚠️ خطا در ارتباط با AI (کد {e.response.status_code}). کمی صبر کن."
    except Exception as e:
        logger.error(f"Unexpected error: {e}")
        return "❌ خطای ناشناخته. لطفاً دوباره تلاش کن."


# ─── Handlers ────────────────────────────────────────────────────────────────

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    text = (
        "👋 سلام! من *Onigama AI Bot* هستم.\n\n"
        "می‌تونی:\n"
        "• هر سوالی بپرسی → مستقیم بنویس\n"
        "• ایده ریل بگیری → `/reel [موضوع]`\n"
        "• راهنما → `/help`"
    )
    await update.message.reply_text(text, parse_mode="Markdown")


async def help_command(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    text = (
        "📖 *راهنمای ربات*\n\n"
        "*دستورات:*\n"
        "`/start` — شروع\n"
        "`/reel [موضوع]` — ایده ریل اینستاگرام\n"
        "`/help` — این راهنما\n\n"
        "*چت آزاد:*\n"
        "هر پیامی بفرستی، AI جواب میده 🤖"
    )
    await update.message.reply_text(text, parse_mode="Markdown")


async def reel_command(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    topic = " ".join(context.args).strip()
    if not topic:
        await update.message.reply_text(
            "📌 موضوع ریل رو بنویس:\n`/reel برندینگ شخصی`",
            parse_mode="Markdown",
        )
        return

    await update.message.chat.send_action("typing")

    system = (
        "You are the Onigama Reel Strategist. "
        "Give ONE viral Instagram reel idea. Reply under 700 characters. "
        "Use EXACTLY this format:\n\n"
        "🎣 *HOOK:* [attention-grabbing opening line]\n"
        "🎬 *REEL IDEA:* [specific concept and visual direction]\n"
        "📣 *CTA:* [clear call to action]\n\n"
        "No extra text. No explanations."
    )

    reply = await call_ai(system, topic, max_tokens=600)
    await update.message.reply_text(reply, parse_mode="Markdown")


async def chat_message(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    user_text = update.message.text.strip()
    if not user_text:
        return

    await update.message.chat.send_action("typing")

    system = (
        "You are a helpful assistant for Telegram. "
        "Keep responses concise and under 800 characters. "
        "Be friendly and direct. "
        "Format nicely using Telegram Markdown when appropriate."
    )

    reply = await call_ai(system, user_text, max_tokens=700)
    await update.message.reply_text(reply, parse_mode="Markdown")


# ─── Main ────────────────────────────────────────────────────────────────────

def main() -> None:
    app = ApplicationBuilder().token(TELEGRAM_TOKEN).build()

    app.add_handler(CommandHandler("start", start))
    app.add_handler(CommandHandler("help", help_command))
    app.add_handler(CommandHandler("reel", reel_command))
    app.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, chat_message))

    logger.info("✅ Bot started. Listening for updates...")
    app.run_polling(drop_pending_updates=True)


if __name__ == "__main__":
    main()
