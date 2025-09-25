</section>
</div>
{if isset($_c['CompanyFooter'])}
    <footer class="main-footer">
        {$_c['CompanyFooter']}
        <div class="pull-right">
            <a href="javascript:showPrivacy()">Privacy</a>
            &bull;
            <a href="javascript:showTaC()">T &amp; C</a>
        </div>
    </footer>
{else}
    <footer class="main-footer">
        PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener"
            target="_blank">iBNuX</a>, Theme by <a href="https://adminlte.io/" rel="nofollow noreferrer noopener"
            target="_blank">AdminLTE</a>
        <div class="pull-right">
            <a href="javascript:showPrivacy()">Privacy</a>
            &bull;
            <a href="javascript:showTaC()">T &amp; C</a>
        </div>
    </footer>
{/if}
</div>


<!-- Modal -->
<div class="modal fade" id="HTMLModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="HTMLModal_konten"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">&times;</button>
            </div>
        </div>
    </div>
</div>



<script src="{$app_url}/ui/ui/scripts/jquery.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/bootstrap.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/adminlte.min.js"></script>

<script src="{$app_url}/ui/ui/scripts/plugins/select2.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/custom.js?2025.2.5"></script>

{if isset($xfooter)}
    {$xfooter}
{/if}

{if $_c['ai_enabled']}
{literal}
<style>
    body .ai-chat-button{position:fixed!important;right:20px!important;bottom:20px!important;z-index:9999!important;border:none!important;border-radius:50px!important;background:linear-gradient(135deg,#3b82f6,#1d4ed8)!important;color:#fff!important;padding:12px 16px!important;font-size:14px!important;font-weight:600!important;line-height:1!important;box-shadow:0 4px 20px rgba(59,130,246,.3)!important;transition:all .3s ease!important}
    body .ai-chat-button:hover{transform:translateY(-2px)!important;box-shadow:0 6px 25px rgba(59,130,246,.4)!important}
    body .ai-chat-panel{position:fixed!important;right:20px!important;bottom:80px!important;width:320px!important;max-width:calc(100vw-40px)!important;height:400px!important;max-height:60vh!important;background:#fff!important;border:1px solid #e5e7eb!important;border-radius:12px!important;box-shadow:0 10px 40px rgba(0,0,0,.15)!important;display:none;flex-direction:column;z-index:10000;font-size:13px!important;backdrop-filter:blur(10px)!important}
    body .ai-chat-header{padding:12px 16px!important;background:linear-gradient(135deg,#f8fafc,#f1f5f9)!important;border-bottom:1px solid #e5e7eb!important;font-weight:600!important;color:#374151!important;display:flex!important;align-items:center!important;justify-content:space-between!important}
    body .ai-chat-header .ai-status{font-size:11px!important;color:#6b7280!important}
    body .ai-chat-body{flex:1 1 auto!important;overflow:auto!important;padding:12px!important}
    body .ai-chat-input{display:flex!important;gap:8px!important;padding:12px 16px!important;border-top:1px solid #e5e7eb!important;background:#f9fafb!important}
    body .ai-chat-input input{flex:1!important;border:1px solid #d1d5db!important;border-radius:8px!important;padding:8px 12px!important;font-size:13px!important;outline:none!important;transition:border-color .2s!important}
    body .ai-chat-input input:focus{border-color:#3b82f6!important}
    body .ai-chat-input button{border:none!important;background:linear-gradient(135deg,#10b981,#059669)!important;color:#fff!important;padding:8px 16px!important;border-radius:8px!important;font-size:13px!important;font-weight:600!important;cursor:pointer!important;transition:all .2s!important}
    body .ai-chat-input button:hover{transform:translateY(-1px)!important;box-shadow:0 4px 12px rgba(16,185,129,.3)!important}
    body .ai-chat-input button:disabled{background:#9ca3af!important;cursor:not-allowed!important;transform:none!important}
    body .ai-msg{margin:8px 0!important;padding:10px 12px!important;border-radius:12px!important;line-height:1.4!important}
    body .ai-user{background:linear-gradient(135deg,#3b82f6,#1d4ed8)!important;color:#fff!important;margin-left:20px!important}
    body .ai-bot{background:#f3f4f6!important;color:#374151!important;margin-right:20px!important;border:1px solid #e5e7eb!important}
    body .ai-typing{display:inline-block!important;margin-left:8px!important}
    body .ai-typing-dot{width:4px!important;height:4px!important;border-radius:50%!important;background:#6b7280!important;display:inline-block!important;margin:0 1px!important;animation:ai-typing 1.4s infinite ease-in-out both!important}
    body .ai-typing-dot:nth-child(1){animation-delay:-0.32s!important}
    body .ai-typing-dot:nth-child(2){animation-delay:-0.16s!important}
    @keyframes ai-typing{0%,80%,100%{transform:scale(0.8)!important;opacity:0.5!important}40%{transform:scale(1)!important;opacity:1!important}}
</style>
{/literal}
<div id="aiOpen" class="ai-chat-button" style="display:inline-flex;align-items:center;gap:8px;writing-mode:horizontal-tb!important;transform:none!important;height:auto!important;width:auto!important;cursor:pointer">
    <i class="fa fa-robot"></i> AI Help
</div>
<div id="aiPanel" class="ai-chat-panel" style="width:300px;height:350px;max-height:50vh;font-size:12px">
    <div class="ai-chat-header">
        <div><i class="fa fa-robot"></i> AI Assistant</div>
        <div class="ai-status" id="aiStatus">Online</div>
    </div>
    <div id="aiBody" class="ai-chat-body"></div>
    <div class="ai-chat-input">
        <input id="aiInput" type="text" placeholder="Ask me anything about PHPNuxBill..." />
        <button id="aiSend">Send</button>
    </div>
</div>
{literal}<script>window.AI_TOKEN = '{/literal}{$_c['api_key']}{literal}';</script>{/literal}
{literal}
<script>
    (function(){
        var openBtn=document.getElementById('aiOpen');
        var panel=document.getElementById('aiPanel');
        var bodyEl=document.getElementById('aiBody');
        var inputEl=document.getElementById('aiInput');
        var sendBtn=document.getElementById('aiSend');
        var statusEl=document.getElementById('aiStatus');
        var isTyping=false;

        function addMsg(cls,txt){
            var d=document.createElement('div');
            d.className='ai-msg '+cls;
            d.textContent=txt;
            bodyEl.appendChild(d);
            bodyEl.scrollTop=bodyEl.scrollHeight;
        }

        function showTyping(){
            if(isTyping) return;
            isTyping=true;
            var d=document.createElement('div');
            d.className='ai-msg ai-bot';
            d.innerHTML='<div class="ai-typing"><span class="ai-typing-dot"></span><span class="ai-typing-dot"></span><span class="ai-typing-dot"></span></div>';
            bodyEl.appendChild(d);
            bodyEl.scrollTop=bodyEl.scrollHeight;
            return d;
        }

        function removeTyping(typingEl){
            if(typingEl && typingEl.parentNode){
                typingEl.parentNode.removeChild(typingEl);
            }
            isTyping=false;
        }

        function callAI(q){
            var typingEl=showTyping();
            statusEl.textContent='Thinking...';
            sendBtn.disabled=true;

            $.ajax({
                url: '{$app_url}/?_route=ai/chat',
                method:'POST',
                dataType:'json',
                data:{message:q, token: (window.AI_TOKEN||'')},
                success:function(res){
                    removeTyping(typingEl);
                    statusEl.textContent='Online';
                    sendBtn.disabled=false;
                    var a=res&&res.result&&res.result.answer?res.result.answer:(res.message||'');
                    addMsg('ai-bot', a||'Sorry, I could not process your request. Please try again.');
                },
                error:function(xhr){
                    removeTyping(typingEl);
                    statusEl.textContent='Error';
                    sendBtn.disabled=false;
                    var msg = 'Request failed. Please check your connection and try again.';
                    if(xhr && xhr.responseText){
                        try{
                            var e=JSON.parse(xhr.responseText);
                            msg=e.message||msg;
                        }catch(e){
                            msg=xhr.responseText;
                        }
                    }
                    addMsg('ai-bot', msg);
                }
            });
        }

        openBtn.addEventListener('click', function(){
            var isVisible=panel.style.display==='flex';
            panel.style.display=isVisible?'none':'flex';
            if(!isVisible){
                inputEl.focus();
                statusEl.textContent='Online';
            }
        });

        sendBtn.addEventListener('click', function(){
            var q=inputEl.value.trim();
            if(!q || isTyping) return;
            inputEl.value='';
            callAI(q);
        });

        inputEl.addEventListener('keydown', function(e){
            if(e.key==='Enter' && !e.shiftKey){
                e.preventDefault();
                sendBtn.click();
            }
        });

        // Welcome message
        setTimeout(function(){
            if(bodyEl.children.length===0){
                addMsg('ai-bot', 'Hello! I\'m your AI assistant for PHPNuxBill. I can help you with questions about vouchers, payments, WiFi access, account management, and more. What would you like to know?');
            }
        }, 1000);
    })();
</script>
{/literal}
{/if}

{if $_c['tawkto'] != ''}
    <!--Start of Tawk.to Script-->
    <script type="text/javascript">
        var isLoggedIn = false;
        var Tawk_API = {
            onLoad: function() {
                Tawk_API.setAttributes({
                    'username'    : '{$_user['username']}',
                    'service'    : '{$_user['service_type']}',
                    'balance'    : '{$_user['balance']}',
                    'account'    : '{$_user['account_type']}',
                    'phone'    : '{$_user['phonenumber']}'
                }, function(error) {
                    console.log(error)
                });

                }
            };
            var Tawk_LoadStart = new Date();
            Tawk_API.visitor = {
                name: '{$_user['fullname']}',
                email: '{$_user['email']}',
                phone: '{$_user['phonenumber']}'
            };
            (function() {
                var s1 = document.createElement("script"),
                    s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src = 'https://embed.tawk.to/{$_c['tawkto']}';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
        <!--End of Tawk.to Script-->
    {/if}

    <script>
        const toggleIcon = document.getElementById('toggleIcon');
        const body = document.body;
        const savedMode = localStorage.getItem('mode');
        if (savedMode === 'dark') {
            body.classList.add('dark-mode');
            toggleIcon.textContent = '🌞';
        }
    
        function setMode(mode) {
            if (mode === 'dark') {
                body.classList.add('dark-mode');
                toggleIcon.textContent = '🌞';
            } else {
                body.classList.remove('dark-mode');
                toggleIcon.textContent = '🌜';
            }
        }
    
        toggleIcon.addEventListener('click', () => {
            if (body.classList.contains('dark-mode')) {
                setMode('light');
                localStorage.setItem('mode', 'light');
            } else {
                setMode('dark');
                localStorage.setItem('mode', 'dark');
            }
        });
    </script>


{literal}
    <script>
        var listAtts = document.querySelectorAll(`[api-get-text]`);
        listAtts.forEach(function(el) {
            $.get(el.getAttribute('api-get-text'), function(data) {
                el.innerHTML = data;
            });
        });
        $(document).ready(function() {
            var listAtts = document.querySelectorAll(`button[type="submit"]`);
            listAtts.forEach(function(el) {
                if (el.addEventListener) { // all browsers except IE before version 9
                    el.addEventListener("click", function() {
                        $(this).html(
                            `<span class="loading"></span>`
                        );
                        setTimeout(() => {
                            $(this).prop("disabled", true);
                        }, 100);
                    }, false);
                } else {
                    if (el.attachEvent) { // IE before version 9
                        el.attachEvent("click", function() {
                            $(this).html(
                                `<span class="loading"></span>`
                            );
                            setTimeout(() => {
                                $(this).prop("disabled", true);
                            }, 100);
                        });
                    }
                }
                $(function() {
                    $('[data-toggle="tooltip"]').tooltip()
                })
            });
        });

        function ask(field, text){
            var txt = field.innerHTML;
            if (confirm(text)) {
                setTimeout(() => {
                    field.innerHTML = field.innerHTML.replace(`<span class="loading"></span>`, txt);
                    field.removeAttribute("disabled");
                }, 5000);
                return true;
            } else {
                setTimeout(() => {
                    field.innerHTML = field.innerHTML.replace(`<span class="loading"></span>`, txt);
                    field.removeAttribute("disabled");
                }, 500);
                return false;
            }
        }

        function setCookie(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }

        function getCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }
    </script>
{/literal}
<script>
setCookie('user_language', '{$user_language}', 365);
</script>
</body>

</html>