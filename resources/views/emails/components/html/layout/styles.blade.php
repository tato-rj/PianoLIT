<style>
.email-container {
    width: {{ \App\Support\EmailLayout::WIDTH }}px;
    max-width: {{ \App\Support\EmailLayout::WIDTH }}px;
    margin: 0 auto;
    table-layout: fixed;
    box-sizing: border-box;
}
.welcome-padding, .content-cell, .welcome-footer {
    padding-left: {{ \App\Support\EmailLayout::DESKTOP_INSET }}px;
    padding-right: {{ \App\Support\EmailLayout::DESKTOP_INSET }}px;
    overflow-wrap: break-word;
}
@media only screen and (max-width: {{ \App\Support\EmailLayout::MOBILE_BREAKPOINT }}px) {
    .email-container { width: 100% !important; }
    .welcome-padding, .content-cell, .welcome-footer {
        padding-left: {{ \App\Support\EmailLayout::MOBILE_INSET }}px !important;
        padding-right: {{ \App\Support\EmailLayout::MOBILE_INSET }}px !important;
    }
}
</style>
