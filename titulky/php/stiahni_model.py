#!/usr/bin/env python3
"""Stiahne model Whisper do priečinka models/, ktorý potom nahráš cez FTP vedľa index.php.

Spusti na počítači s internetom (nie na hostingu):
    pip install huggingface_hub
    python3 stiahni_model.py            # model small (približne 250 MB), odporúčané
    python3 stiahni_model.py --turbo    # navyše large-v3-turbo (približne 1,5 GB, pre prehliadače s WebGPU)

Výsledok: priečinok models/ s podpriečinkami Xenova/whisper-small a onnx-community/whisper-large-v3-turbo.
Nahraj celý priečinok models/ na FTP do rovnakého priečinka ako index.php.
Stránka potom model sťahuje z tvojho servera a nie z huggingface.co.
"""
import os
import sys

MODELS = {
    "small": {
        "repo": "Xenova/whisper-small",
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
    keys = ["small"] + (["turbo"] if "--turbo" in sys.argv or "--all" in sys.argv else [])
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
    print("\nHotovo. Nahraj priečinok 'models' na FTP vedľa index.php.")


if __name__ == "__main__":
    main()
