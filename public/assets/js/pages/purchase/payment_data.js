// Biến JS được chèn từ PHP để JS xử lý, tách riêng ra file này để load qua <script src>
window.PAYMENT_PAGE_DATA = {
  isTrial: JS_IS_TRIAL,
  isRenewal: JS_IS_RENEWAL,
  basePrice: JS_BASE_PRICE,
  vatValue: JS_VAT_VALUE,
  currentPrice: JS_CURRENT_PRICE,
  orderDescription: JS_ORDER_DESCRIPTION,
  baseUrl: JS_BASE_URL,
  csrfToken: JS_CSRF_TOKEN,
  vietqrBankId: JS_VIETQR_BANK_ID,
  vietqrAccountNo: JS_VIETQR_ACCOUNT_NO,
  vietqrImageTemplate: JS_VIETQR_IMAGE_TEMPLATE,
  vietqrAccountName: JS_VIETQR_ACCOUNT_NAME
};
