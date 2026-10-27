<style>
  .vb-card{background:#fff;border:1px solid #E6E4DC;border-radius:16px;padding:20px;margin-bottom:16px}
  .vb-card.flush{padding:0;overflow:hidden}
  .vb-title{font-weight:700;font-size:17px;color:#1d1a0f}.vb-sub{font-size:13px;color:#6b6652;margin-top:2px}
  .vb-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
  .vb-grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
  @media(max-width:900px){.vb-grid2,.vb-grid3,.vb-sum{grid-template-columns:1fr!important}}
  .vb-chip{border:1px solid #F3EFDD;background:#FFFCF0;border-radius:12px;padding:10px 14px;font-size:12.5px;color:#4a4636;line-height:1.6}
  .vb-wrap{overflow-x:auto}
  .vb-table{width:100%;border-collapse:collapse;font-size:13.5px}
  .vb-table th{background:#FAF8F0;text-align:left;font-size:12px;font-weight:700;padding:10px 12px;color:#6b6652;border-bottom:1px solid #E6E4DC;white-space:nowrap}
  .vb-table td{padding:10px 12px;border-bottom:1px solid #F3EFDD;vertical-align:top}
  .vb-hanzi{font-size:18px;font-weight:700}
  .vb-input,.vb-field input,.vb-field select{width:100%;border:1px solid #E6E4DC;border-radius:10px;padding:9px 12px;font:inherit;font-size:13.5px;background:#fff}
  .vb-input:focus,.vb-field input:focus,.vb-field select:focus{outline:2px solid #FFD60A;border-color:#FFD60A}
  .vb-field{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}.vb-field label{font-size:12px;font-weight:700;color:#6b6652}
  .vb-btn{background:#FFD60A;color:#1A1A1A;border:0;border-radius:10px;padding:9px 16px;font:inherit;font-weight:700;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:7px}
  .vb-btn:hover{background:#FFE34D}
  .vb-btn-out{background:#fff;color:#1A1A1A;border:1.5px solid #1A1A1A;border-radius:22px;padding:6px 14px;font:inherit;font-weight:600;font-size:12px;cursor:pointer}
  .vb-tabs{display:flex;gap:6px;background:#F5F4EE;padding:4px;border-radius:11px;width:fit-content;margin-bottom:18px}
  .vb-tab{font-size:12.5px;font-weight:700;padding:8px 18px;border-radius:8px;color:#6b6652;text-decoration:none}
  .vb-tab.on{background:#fff;color:#5c4500;box-shadow:0 1px 3px rgba(0,0,0,.08)}
  .vb-pill{font-size:10.5px;font-weight:800;padding:3px 10px;border-radius:20px;display:inline-block;white-space:nowrap}
  .s-hafal{background:#DDF1E1;color:#2C6B3F}.s-ragu{background:#FFF0B8;color:#7A5C00}.s-salah{background:#FCE0DC;color:#B3382A}.s-blank{background:#F5F4EE;color:#9CA294}
  .vb-stars{color:#E0A62F;letter-spacing:1px;font-size:15px;white-space:nowrap}.vb-stars i{font-style:normal;color:#D8D6CC}
  .vb-bar{height:10px;background:#F5F4EE;border-radius:6px;overflow:hidden}.vb-bar div{height:100%;background:#FFD60A;border-radius:6px;transition:.3s}
  .vb-counter{display:flex;align-items:center;gap:14px;background:#F5F4EE;border-radius:12px;padding:12px 16px;margin-top:10px;flex-wrap:wrap}
  .vb-counter .n{font-weight:700;color:#5c4500;display:flex;align-items:center;gap:6px;white-space:nowrap}
  .vb-counter .note{font-size:12px;color:#6b6652;margin-left:auto}
  .vb-sum{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px}
  .vb-sum>div{border-radius:16px;padding:16px 18px;border:1px solid}.vb-sum b{font-size:13px}.vb-sum small{display:block;font-size:11.5px;opacity:.8}.vb-sum .num{font-size:28px;font-weight:800;margin-top:4px}
  .c-ragu{background:#FFF6D0;border-color:#F2DB80;color:#7A5C00}.c-salah{background:#FDEBE8;border-color:#F3BDB5;color:#B3382A}.c-benar{background:#E6F5E9;border-color:#BCE0C4;color:#2C6B3F}
  .vb-legend{padding:14px 18px;font-size:12.5px;line-height:1.9;color:#6b6652;border-top:1px solid #E6E4DC}
  .vb-fchip{border:1px solid #E6E4DC;border-radius:20px;padding:5px 14px;font-size:12px;font-weight:600;color:#4a4636;background:#fff;cursor:pointer;text-decoration:none}
  .vb-fchip.on{background:#FFEFA0;border-color:#FFD60A;color:#5c4500}
  .vb-bubble{background:#FFF8D6;border-radius:12px;padding:8px 12px;font-size:12px;color:#5c4500}
  .vb-audio{width:30px;height:30px;border-radius:50%;background:#FFD60A;border:0;cursor:pointer;font-size:12px}
  .vb-exrow td{background:#FFF8D6;font-size:12.5px}
  .vb-ex-item{margin-bottom:6px}.vb-ex-item b{font-size:14px;margin-right:8px}.vb-ex-item span{color:#6b6652;margin-right:8px}
  .vb-eye{background:#FFF8D6;border:1px solid #F2DB80;border-radius:10px;width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:#5c4500}
  .vb-eye:hover,.vb-eye.on{background:#FFD60A}.vb-eye .off{display:none}.vb-eye.on .off{display:block}.vb-eye.on .on-ico{display:none}
  .vb-ul{margin:0;padding-left:16px;font-size:12.5px;line-height:1.6}
  .vb-kv{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px dashed #F2DB80;font-size:13.5px}.vb-kv span:first-child{color:#6b6652}.vb-kv span:last-child{font-weight:700}
  .vb-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1A1A1A;color:#fff;padding:10px 18px;border-radius:12px;font-size:13px;z-index:99;display:none}
  .vb-flash{background:#E6F5E9;border:1px solid #BCE0C4;color:#2C6B3F;border-radius:12px;padding:10px 14px;margin-bottom:14px;font-size:13px}
</style>
