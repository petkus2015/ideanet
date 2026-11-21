#!/usr/bin/env python3
"""Stiahne jazykové modely Subgen do priečinka models/, ktorý potom nahráš cez FTP vedľa index.php.

Spusti na počítači s internetom (nie na hostingu):
    pip install huggingface_hub
    python3 stiahni_model.py            # rozpoznávanie reči (Whisper small, ~250 MB) + preklad titulkov (NLLB-200, niekoľko stoviek MB)
    python3 stiahni_model.py --turbo    # navyše presnejšie rozpoznávanie large-v3-turbo (~1,5 GB, pre prehliadače s WebGPU)

Na Windows namiesto "python3" napíš "py" (napr. py stiahni_model.py).

Výsledok: priečinok models/ s podpriečinkami Xenova/whisper-small, Xenova/nllb-200-distilled-600M
(a onnx-community/whisper-large-v3-turbo). Nahraj celý priečinok models/ na FTP do rovnakého
priečinka ako index.php. Stránka potom modely sťahuje z tvojho servera a nie z huggingface.co.
"""
import os
import sys

MODELS = {
    "small": {
        "repo": "Xenova/whisper-small",
        "onnx": {"encoder_model_quantized.onnx", "decoder_model_merged_quantized.onnx"},
    },
    "preklad": {  # preklad titulkov medzi 9 jazykmi aplikácie
        "repo": "Xenova/nllb-200-distilled-600M",
        "onnx": {"encoder_model_quantized.onnx", "decoder_model_merged_quantized.onnx"},
    },
    "turbo": {
        "repo": "onnx-community/whisper-large-v3-turbo",
        "onnx": {"encoder_model_fp16.onnx", "decoder_model_merged_q4.onnx"},
    },
}
SKIP = (".md", ".gitattributes")


def select(files, wanted_onnx):
    """Z názvov súborov v repozitári vyberie konfiguráciu, tokenizér a potrebné súbory ONNX."""
    out = []
    for f in files:
        if f.endswith(SKIP) or f == ".gitattributes":
            continue
        if f.startswith("onnx/"):
            name = f[len("onnx/"):]
            stems = {w for w in wanted_onnx}
            # k modelu patria aj súbory s dátami (napr. encoder_model_fp16.onnx_data)
            if name in stems or any(name.startswith(w + "_data") for w in stems):
                out.append(f)
        elif "/" not in f:
            out.append(f)
    return sorted(out)


def main():
    try:
        from huggingface_hub import HfApi, hf_hub_download
    except ImportError:
        sys.exit("Najprv spusti: pip install huggingface_hub")
    keys = ["small", "preklad"] + (["turbo"] if "--turbo" in sys.argv or "--all" in sys.argv else [])
    api, root = HfApi(), os.path.join(os.getcwd(), "models")
    for key in keys:
        cfg = MODELS[key]
        files = api.list_repo_files(cfg["repo"])
        chosen = select(files, cfg["onnx"])
        missing = [w for w in cfg["onnx"] if "onnx/" + w not in files]
        if missing:
            onnx_files = [f for f in files if f.startswith("onnx/")]
            sys.exit(f"V repozitári {cfg['repo']} chýba {missing}. Dostupné súbory ONNX: {onnx_files}")
        target = os.path.join(root, *cfg["repo"].split("/"))
        print(f"== {cfg['repo']}: {len(chosen)} súborov do {target}")
        for f in chosen:
            print("  ", f)
            hf_hub_download(cfg["repo"], f, local_dir=target)
        size = sum(os.path.getsize(os.path.join(dp, n)) for dp, _, fs in os.walk(target) for n in fs)
        print(f"   spolu {size / 1048576:.0f} MB")
    print("\nHotovo. Nahraj priečinok 'models' na FTP vedľa index.php.")


if __name__ == "__main__":
    main()
