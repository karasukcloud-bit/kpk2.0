from PIL import Image, ImageDraw
from pathlib import Path

src = Image.open(r"d:\OSPanel\domains\localhost\kpk2.0\assets\img\icon-512.png").convert("RGBA")
res = Path(r"d:\OSPanel\domains\localhost\kpk2.0\mobile\android\app\src\main\res")
out_dir = Path(r"d:\OSPanel\domains\localhost\kpk2.0\mobile\resources\icons")
out_dir.mkdir(parents=True, exist_ok=True)

BG = (8, 44, 176, 255)


def fit_on_canvas(src_img, size, pad_ratio=0.12, bg=None):
    canvas = Image.new("RGBA", (size, size), bg if bg is not None else (0, 0, 0, 0))
    pad = int(size * pad_ratio)
    box = max(1, size - pad * 2)
    bbox = src_img.getbbox()
    cropped = src_img.crop(bbox) if bbox else src_img
    cropped = cropped.copy()
    cropped.thumbnail((box, box), Image.Resampling.LANCZOS)
    x = (size - cropped.width) // 2
    y = (size - cropped.height) // 2
    canvas.paste(cropped, (x, y), cropped)
    return canvas


def circle_mask(size):
    mask = Image.new("L", (size, size), 0)
    draw = ImageDraw.Draw(mask)
    draw.ellipse((0, 0, size - 1, size - 1), fill=255)
    return mask


sizes = {
    "mipmap-mdpi": 48,
    "mipmap-hdpi": 72,
    "mipmap-xhdpi": 96,
    "mipmap-xxhdpi": 144,
    "mipmap-xxxhdpi": 192,
}
fg_sizes = {
    "mipmap-mdpi": 108,
    "mipmap-hdpi": 162,
    "mipmap-xhdpi": 216,
    "mipmap-xxhdpi": 324,
    "mipmap-xxxhdpi": 432,
}

for folder, size in sizes.items():
    icon = fit_on_canvas(src, size, pad_ratio=0.14, bg=BG)
    icon.save(res / folder / "ic_launcher.png", "PNG")
    round_icon = icon.copy()
    round_icon.putalpha(circle_mask(size))
    round_icon.save(res / folder / "ic_launcher_round.png", "PNG")

for folder, size in fg_sizes.items():
    fg = fit_on_canvas(src, size, pad_ratio=0.22, bg=None)
    fg.save(res / folder / "ic_launcher_foreground.png", "PNG")

fit_on_canvas(src, 512, pad_ratio=0.12, bg=BG).save(out_dir / "icon-512.png", "PNG")
fit_on_canvas(src, 1024, pad_ratio=0.12, bg=BG).save(out_dir / "icon-1024.png", "PNG")
fit_on_canvas(src, 512, pad_ratio=0.18, bg=None).save(out_dir / "icon-foreground-512.png", "PNG")

print("OK")
for folder in sizes:
    im = Image.open(res / folder / "ic_launcher.png")
    print(folder, im.size)
