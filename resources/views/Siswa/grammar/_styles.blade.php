@extends('layouts.app')

@section('title', ($title ?? 'Dashboard') . ' — Haoyou')

@push('styles')
<style>
    .gr-card{background:#fff;border:1px solid #F3EFDD;border-radius:28px;padding:24px;
        box-shadow:0 2px 10px rgba(90,70,0,.04);}
    .gr-box{background:#F5F3EA;border-radius:20px;padding:16px;}
    .gr-input{width:100%;height:48px;border:1px solid #EAE7D9;border-radius:14px;background:#fff;
        padding:0 16px;font-size:14px;font-family:inherit;outline:none;transition:.15s;}
    .gr-input:focus{border-color:#E5C85B;box-shadow:0 0 0 3px #FBF0BE;}
    .gr-star{display:inline-flex;align-items:center;gap:6px;font-size:14px;color:#9a9580;white-space:nowrap;}
    .gr-star b{color:#5c4500;font-weight:700;}
    .gr-hint{font-size:13px;color:#6b6652;margin-top:12px;}

    .gr-table-wrap{background:#fff;border:1px solid #F3EFDD;border-radius:24px;overflow-x:auto;margin-top:20px;}
    .gr-table{width:100%;border-collapse:collapse;min-width:900px;}
    .gr-table th{font-size:11.5px;letter-spacing:.06em;text-transform:uppercase;color:#a3ab97;
        font-weight:700;text-align:left;padding:18px 16px;border-bottom:1px solid #F3EFDD;}
    .gr-table td{padding:16px;font-size:14px;color:#1d1a0f;border-bottom:1px solid #F3EFDD;vertical-align:middle;}
    .gr-table tr:last-child td{border-bottom:0;}
    .gr-table tbody tr.is-clickable{cursor:pointer;transition:.15s;}
    .gr-table tbody tr.is-clickable:hover{background:#FDFAEC;}
    .gr-table tbody tr.is-learned{background:#FBF0BE55;}
    .gr-sub{display:block;font-size:12.5px;color:#7a7660;}
    .gr-ul{margin:0;padding-left:16px;font-size:12.5px;color:#6b6652;line-height:1.9;}

    /* Status badge */
    .badge{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.04em;
        text-transform:uppercase;padding:6px 12px;border-radius:999px;}
    .badge.benar{background:#E3F1E5;color:#2F6B3F;}
    .badge.kurang{background:#FBF0BE;color:#7a5a00;}
    .badge.salah{background:#FCE4E0;color:#C0392B;}

    /* Pilihan test */
    .chip{border:1px solid #EAE7D9;background:#fff;border-radius:999px;padding:8px 16px;
        font-size:15px;font-weight:600;font-family:inherit;color:#1d1a0f;cursor:pointer;transition:.15s;}
    .chip:hover{background:#FBF0BE;}
    .chip.sel{background:#FBF0BE;border-color:#E5C85B;}

    /* Progress */
    .gr-bar{height:12px;background:#EFEBDB;border-radius:999px;overflow:hidden;margin:14px 0;}
    .gr-bar > i{display:block;height:100%;background:#ffe135;border-radius:999px;transition:width .3s;}
</style>