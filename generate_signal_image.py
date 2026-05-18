#!/usr/bin/env python3
import sys
import json
from PIL import Image, ImageDraw, ImageFont
from datetime import datetime

def create_signal_image(signal, price_data, output_path):
    # ابعاد تصویر - طراحی premium
    W, H = 1200, 1600
    
    # پالت رنگ بر اساس BUY/SELL
    is_buy = signal['signal'] == 'BUY'
    
    if is_buy:
        bg_dark = (4, 4, 10)
        bg_mid = (12, 12, 24)
        accent = (56, 176, 168)  # تیل
        accent_glow = (56, 176, 168, 80)
        direction_color = (76, 196, 188)
    else:
        bg_dark = (10, 4, 4)
        bg_mid = (24, 12, 12)
        accent = (208, 69, 69)  # قرمز
        accent_glow = (208, 69, 69, 80)
        direction_color = (228, 89, 89)
    
    gold = (201, 185, 110)
    white = (232, 232, 240)
    muted = (96, 96, 160)
    
    # ساخت تصویر
    img = Image.new('RGB', (W, H), bg_dark)
    draw = ImageDraw.Draw(img, 'RGBA')
    
    # ─── Background Gradient ──────────────────────────────────────────────────
    for y in range(H):
        ratio = y / H
        r = int(bg_dark[0] + (bg_mid[0] - bg_dark[0]) * ratio)
        g = int(bg_dark[1] + (bg_mid[1] - bg_dark[1]) * ratio)
        b = int(bg_dark[2] + (bg_mid[2] - bg_dark[2]) * ratio)
        draw.rectangle([(0, y), (W, y+1)], fill=(r, g, b))
    
    # ─── Accent Glow ──────────────────────────────────────────────────────────
    draw.ellipse([(W//2-300, -100), (W//2+300, 400)], fill=accent_glow)
    
    # ─── Header ───────────────────────────────────────────────────────────────
    y = 80
    
    # ONIGAMA
    draw.text((W//2, y), "ONIGAMA", font=ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 48), fill=gold, anchor="mm")
    y += 60
    
    # SIGNAL ENGINE
    draw.text((W//2, y), "SIGNAL ENGINE", font=ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 18), fill=muted, anchor="mm")
    y += 80
    
    # ─── Direction Badge ──────────────────────────────────────────────────────
    badge_w, badge_h = 400, 120
    badge_x = (W - badge_w) // 2
    
    # Background
    draw.rounded_rectangle(
        [(badge_x, y), (badge_x + badge_w, y + badge_h)],
        radius=16,
        fill=bg_mid,
        outline=accent,
        width=3
    )
    
    # Direction Text
    direction = "🟢 BUY SIGNAL" if is_buy else "🔴 SELL SIGNAL"
    draw.text((W//2, y + badge_h//2), direction, font=ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 42), fill=direction_color, anchor="mm")
    
    y += badge_h + 100
    
    # ─── Main Data ────────────────────────────────────────────────────────────
    font_label = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 20)
    font_value = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 36)
    
    data_items = [
        ("💰 ENTRY", str(signal.get('entry', 'N/A'))),
        ("🛡 STOP LOSS", str(signal.get('sl', 'N/A'))),
        ("🎯 TARGET 1", str(signal.get('tp1', 'N/A'))),
        ("🎯 TARGET 2", str(signal.get('tp2', 'N/A'))),
        ("📊 RISK:REWARD", str(signal.get('rr', 'N/A'))),
    ]
    
    for label, value in data_items:
        # Card background
        draw.rounded_rectangle(
            [(100, y), (W-100, y+90)],
            radius=12,
            fill=bg_mid,
            outline=(40, 40, 60),
            width=2
        )
        
        # Label
        draw.text((130, y+25), label, font=font_label, fill=muted)
        
        # Value
        draw.text((W-130, y+55), value, font=font_value, fill=white, anchor="rm")
        
        y += 110
    
    # ─── Analysis Section ─────────────────────────────────────────────────────
    y += 40
    
    draw.text((W//2, y), "ANALYSIS", font=ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 24), fill=gold, anchor="mm")
    y += 60
    
    analysis_font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 18)
    
    analysis_lines = [
        f"Bias: {signal.get('bias', 'N/A')}",
        f"Structure: {signal.get('structure', 'N/A')}",
        f"Entry Reason: {signal.get('entry_reason', 'N/A')}",
    ]
    
    for line in analysis_lines:
        draw.text((W//2, y), line, font=analysis_font, fill=(176, 176, 208), anchor="mm")
        y += 40
    
    # ─── Confidence Badge ─────────────────────────────────────────────────────
    y += 30
    confidence = signal.get('confidence', 'Medium')
    conf_emoji = {'High': '🔥', 'Medium': '⚡', 'Low': '⚠️'}.get(confidence, '⚡')
    
    draw.text((W//2, y), f"{conf_emoji} Confidence: {confidence}", font=ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 22), fill=accent, anchor="mm")
    
    # ─── Footer ───────────────────────────────────────────────────────────────
    y = H - 120
    
    draw.text((W//2, y), "⚠️ Not Financial Advice • Trade at Your Own Risk", font=ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 14), fill=muted, anchor="mm")
    y += 40
    
    utc_time = datetime.utcnow().strftime('%H:%M UTC')
    draw.text((W//2, y), f"🧠 Onigama AI • {utc_time} • bot.onigama.com", font=ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 14), fill=muted, anchor="mm")
    
    # ─── Save ─────────────────────────────────────────────────────────────────
    img.save(output_path, 'PNG', quality=95)
    print(f"Image saved: {output_path}")

if __name__ == '__main__':
    if len(sys.argv) != 4:
        print("Usage: generate_signal_image.py <signal_json> <price_json> <output_path>")
        sys.exit(1)
    
    signal_data = json.loads(sys.argv[1])
    price_data = json.loads(sys.argv[2])
    output = sys.argv[3]
    
    create_signal_image(signal_data, price_data, output)
