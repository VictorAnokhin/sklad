<footer class="footer site-footer" role="contentinfo">
    <div class="site-footer__inner">
        <span class="site-footer__seg site-footer__company">{{ $siteFooterCompanyName }}</span>
        <span class="site-footer__seg">Проектов: {{ number_format($siteFooterProjectsCount, 0, ',', ' ') }}</span>
        <span class="site-footer__seg">Пользователей: {{ number_format($siteFooterUsersCount, 0, ',', ' ') }}</span>
        <span class="site-footer__seg">Платформа:
            <a href="{{ $siteFooterPlatformUrl }}" class="site-footer__link" target="_blank" rel="noopener noreferrer">{{ $siteFooterPlatformLabel }}</a>
        </span>
        <span class="site-footer__seg">Банк:
            <a href="{{ $siteFooterBankUrl }}" class="site-footer__link" target="_blank" rel="noopener noreferrer">{{ $siteFooterBankLabel }}</a>
        </span>
    </div>
</footer>
