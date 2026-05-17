#!/usr/bin/env python3
"""
Onigama AI Brain — Image Generator
تولید تصویر حرفه‌ای برای پیام‌های تلگرام
"""

from PIL import Image, ImageDraw, ImageFont
import sys
import json
import os

FONT_DIR  = '/usr/share/fonts/truetype/liberation/'
LOGO_PATH = os.path.join(os.path.dirname(__file__), 'logo_transparent.png')
OUT_DIR   = '/tmp/onigama_imgs/'

os.makedirs(OUT_DIR, exist_ok=True)

W, H = 1080, 1350

# ─── رنگ‌های برند ─────────────────────────────────────────────
DARK     = (6,   6,   12)
CARD     = (14,  14,  24)
BORDER   = (30,  30,  52)
GOLD     = (201, 185, 110)
GOLD_DIM = (130, 118, 68)
WHITE    = (235, 235, 250)
DESC     = (175, 175, 205)
LABEL    = (120, 120, 155)
RED      = (210, 70,  70)
GREEN    = (55,  185, 115)
TEAL     = (40,  175, 168)

# ─── رنگ‌های دستورها ──────────────────────────────────────────
CMD_COLORS = {
    'XAUUSD':   GOLD,
    'EURUSD':   TEAL,
    'GBPUSD':   (100, 149, 237),
    'USDJPY':   (220, 160, 60),
    'USDCHF':   (150, 200, 150),
    'SESSION':  TEAL,
    'NEWS':     (200, 100, 60),
    'SETUP':    (150, 100, 220),
    'JOURNAL':  GREEN,
    'CHECKLIST':GOLD,
    'REEL':     (200, 80,  150),
    'PSYCH':    (100, 180, 220),
    'MTF':      WHITE,
}

def load_fonts():
    try:
        return {
            'bold':   ImageFont.truetype(FONT_DIR + 'LiberationSans-BoldItalic.ttf', 28),
            'bold_lg':ImageFont.truetype(FONT_DIR + 'LiberationSans-BoldItalic.ttf', 42),
            'bold_xx':ImageFont.truetype(FONT_DIR + 'LiberationSans-BoldItalic.ttf', 80),
            'mono':   ImageFont.truetype(FONT_DIR + 'LiberationMono-Bold.ttf', 22),
            'reg_sm': ImageFont.truetype(FONT_DIR + 'LiberationSerif-Regular.ttf', 22),
            'tiny':   ImageFont.truetype(FONT_DIR + 'LiberationSans-BoldItalic.ttf', 20),
            'label':  ImageFont.truetype(FONT_DIR + 'LiberationSans-BoldItalic.ttf', 19),
        }
    except:
        default = ImageFont.load_default()
        return {k: default for k in ['bold','bold_lg','bold_xx','mono','reg_sm','tiny','label']}

def wrap(text, n=52):
    if len(text) <= n: return text
    idx = text[:n].rfind(' ')
    return text[:idx] + '...' if idx > 0 else text[:n] + '...'

def paste_logo(img, logo, size=130):
    if not os.path.exists(LOGO_PATH):
        return
    lh = size
    lw = int(lh * (logo.width / logo.height))
    lr = logo.resize((lw, lh), Image.LANCZOS)
    img.paste(lr, ((W - lw) // 2, 38), lr)

def draw_card(draw, y, icon, label, value, desc, color):
    draw.rounded_rectangle([60, y, W-60, y+104], radius=12,
                           fill=(*CARD, 255), outline=(*BORDER, 255), width=1)
    draw.rectangle([60, y+14, 65, y+90], fill=(*color, 255))
    draw.text((90, y+14), f'{icon}  {label}', font=F['label'], fill=(*LABEL, 255))
    draw.text((90, y+42), value, font=F['bold'], fill=(*color, 255))
    draw.text((90, y+74), wrap(desc), font=F['reg_sm'], fill=(*DESC, 255))

def make_base(module_label, title, accent_color=None):
    """ساخت قاب پایه مشترک همه تصاویر"""
    color = accent_color or GOLD
    img   = Image.new('RGBA', (W, H), (*DARK, 255))
    draw  = ImageDraw.Draw(img)

    draw.rectangle([0, 0, W, 5], fill=(*color, 255))

    if os.path.exists(LOGO_PATH):
        logo = Image.open(LOGO_PATH).convert('RGBA')
        paste_logo(img, logo, 130)

    draw.rectangle([80, 195, W-80, 196], fill=(*BORDER, 255))
    draw.text((W//2, 220), f'ONIGAMA  ·  {module_label}', font=F['tiny'], fill=(*GOLD_DIM, 255), anchor='mm')
    draw.text((W//2, 262), title, font=F['bold_lg'], fill=(*WHITE, 255), anchor='mm')

    return img, draw

def generate_market(data: dict) -> str:
    """تولید تصویر تحلیل بازار"""
    symbol  = data.get('symbol', 'XAUUSD')
    price   = data.get('price', '')
    change  = data.get('change', '')
    changep = data.get('change_pct', '')
    high    = data.get('high', '')
    low     = data.get('low', '')
    rows    = data.get('rows', [])
    color   = CMD_COLORS.get(symbol, GOLD)

    img, draw = make_base('INTELLIGENCE', f'{symbol} — ICT/SMC', color)

    # قیمت
    if price:
        draw.text((W//2, 340), price, font=F['bold_xx'], fill=(*color, 255), anchor='mm')
    if change:
        sign_color = RED if '-' in str(change) else GREEN
        draw.text((W//2 - 80, 410), f'▼  {change}', font=F['mono'], fill=(*sign_color, 255), anchor='mm')
        draw.text((W//2 + 70, 410), f'{changep}%', font=F['mono'], fill=(*sign_color, 255), anchor='mm')

    # High/Low
    if high or low:
        draw.rounded_rectangle([80, 430, W-80, 472], radius=8, fill=(*CARD, 255), outline=(*BORDER, 255), width=1)
        draw.text((200, 451), f'L   {low}', font=F['mono'], fill=(*RED, 255), anchor='mm')
        draw.text((W//2, 451), '│', font=F['mono'], fill=(*BORDER, 255), anchor='mm')
        draw.text((W-200, 451), f'H   {high}', font=F['mono'], fill=(*GREEN, 255), anchor='mm')

    draw.rectangle([80, 488, W-80, 489], fill=(*BORDER, 255))

    y = 502
    for row in rows:
        row_color_name = row.get('color', 'white')
        row_color = {'red': RED, 'gold': GOLD, 'teal': TEAL, 'green': GREEN, 'white': WHITE}.get(row_color_name, WHITE)
        draw_card(draw, y, row['icon'], row['label'], row['value'], row['desc'], row_color)
        y += 114

    _draw_footer(draw, symbol)
    return _save(img, f'market_{symbol}')

def generate_text(data: dict) -> str:
    """تولید تصویر برای پیام‌های متنی (سشن، نیوز، ژورنال، سایر)"""
    module = data.get('module', 'SYSTEM')
    title  = data.get('title', '')
    rows   = data.get('rows', [])
    tag    = data.get('tag', module)
    color  = CMD_COLORS.get(tag, TEAL)

    img, draw = make_base(module, title, color)

    draw.rectangle([80, 290, W-80, 291], fill=(*BORDER, 255))

    y = 310
    for row in rows:
        row_color_name = row.get('color', 'white')
        row_color = {'red': RED, 'gold': GOLD, 'teal': TEAL, 'green': GREEN, 'white': WHITE, 'muted': LABEL}.get(row_color_name, WHITE)

        # اگر فقط یک خط توضیح باشد
        if row.get('type') == 'section':
            draw.rectangle([80, y, W-80, y+1], fill=(*BORDER, 255))
            draw.text((W//2, y+18), row.get('text', ''), font=F['tiny'], fill=(*GOLD_DIM, 255), anchor='mm')
            y += 40
        else:
            draw.rounded_rectangle([60, y, W-60, y+104], radius=12,
                                   fill=(*CARD, 255), outline=(*BORDER, 255), width=1)
            draw.rectangle([60, y+14, 65, y+90], fill=(*row_color, 255))
            draw.text((90, y+14), f'{row.get("icon","")}  {row.get("label","")}', font=F['label'], fill=(*LABEL, 255))
            draw.text((90, y+42), row.get('value', ''), font=F['bold'], fill=(*row_color, 255))
            draw.text((90, y+74), wrap(row.get('desc', '')), font=F['reg_sm'], fill=(*DESC, 255))
            y += 114

        if y > H - 160:
            break

    _draw_footer(draw, tag)
    return _save(img, f'text_{tag.lower()}')

def _draw_footer(draw, tag):
    draw.rectangle([80, H-90, W-80, H-89], fill=(*BORDER, 255))
    draw.rectangle([0, H-5, W, H], fill=(*GOLD, 255))
    draw.text((W//2, H-65), 'ONIGAMA AI BRAIN', font=F['bold'], fill=(*GOLD_DIM, 255), anchor='mm')
    draw.text((W//2, H-34), f'bot.onigama.com  ·  #{tag}', font=F['tiny'], fill=(*DESC, 255), anchor='mm')

def _save(img, name) -> str:
    path = os.path.join(OUT_DIR, f'{name}.jpg')
    img.convert('RGB').save(path, 'JPEG', quality=97)
    return path

# ─── اجرا از خط فرمان ─────────────────────────────────────────
if __name__ == '__main__':
    F = load_fonts()
    data = json.loads(sys.argv[1]) if len(sys.argv) > 1 else {}
    mode = data.get('mode', 'market')

    if mode == 'market':
        path = generate_market(data)
    else:
        path = generate_text(data)

    print(path)
