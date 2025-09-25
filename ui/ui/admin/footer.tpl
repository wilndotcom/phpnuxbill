</section>
</div>
<footer class="main-footer">
    <div class="pull-right" id="version" onclick="location.href = '{Text::url('community')}#latestVersion';"></div>
    PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener"
        target="_blank">iBNuX</a>, Theme by <a href="https://adminlte.io/" rel="nofollow noreferrer noopener"
        target="_blank">AdminLTE</a>
</footer>
</div>
<script src="{$app_url}/ui/ui/scripts/jquery.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/bootstrap.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/adminlte.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/plugins/select2.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/pace.min.js"></script>
<script src="{$app_url}/ui/ui/summernote/summernote.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/custom.js?2025.2.5"></script>

<script>
    (function(){
        var openS = document.getElementById('openSearch');
        var closeS = document.getElementById('closeSearch');
        var term = document.getElementById('searchTerm');
        if(openS){
            openS.addEventListener('click', function(){ var ov = document.getElementById('searchOverlay'); if(ov){ ov.style.display='flex'; }});
        }
        if(closeS){
            closeS.addEventListener('click', function(){ var ov = document.getElementById('searchOverlay'); if(ov){ ov.style.display='none'; }});
        }
        if(term){
            term.addEventListener('keyup', function(){
                var query = this.value;
                $.ajax({ url: '{Text::url('search_user')}', type: 'GET', data: { query: query }, success: function (data) {
                    if (data.trim() !== '') { $('#searchResults').html(data).show(); } else { $('#searchResults').html('').hide(); }
                }});
            });
        }
    })();
    </script>

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

{if isset($xfooter)}
    {$xfooter}
{/if}
{if $_c['ai_enabled']}
{literal}
<style>
    body .ai-chat-button{position:fixed!important;right:14px!important;bottom:14px!important;z-index:9999!important;border:none!important;border-radius:18px!important;background:#3b82f6!important;color:#fff!important;padding:6px 10px!important;font-size:12px!important;line-height:1!important;box-shadow:0 2px 10px rgba(0,0,0,.18)!important}
    body .ai-chat-panel{position:fixed!important;right:14px!important;bottom:56px!important;width:280px!important;max-width:88vw!important;height:330px!important;max-height:62vh!important;background:#fff!important;border:1px solid #ddd!important;border-radius:8px!important;box-shadow:0 6px 18px rgba(0,0,0,.18)!important;display:none;flex-direction:column;z-index:10000;font-size:12px!important}
    body .ai-chat-header{padding:6px 8px!important;background:#f3f4f6!important;border-bottom:1px solid #e5e7eb!important;font-weight:600!important}
    body .ai-chat-body{flex:1 1 auto!important;overflow:auto!important;padding:6px!important}
    body .ai-chat-input{display:flex!important;gap:6px!important;padding:6px!important;border-top:1px solid #e5e7eb!important}
    body .ai-chat-input input{flex:1!important;border:1px solid #d1d5db!important;border-radius:6px!important;padding:6px!important;font-size:12px!important}
    body .ai-chat-input button{border:none!important;background:#10b981!important;color:#fff!important;padding:6px 10px!important;border-radius:6px!important;font-size:12px!important}
    body .ai-msg{margin:6px 0!important;padding:6px 8px!important;border-radius:6px!important}
    body .ai-user{background:#e0f2fe!important}
    body .ai-bot{background:#f1f5f9!important}
</style>
{/literal}
<div id="aiOpen" class="ai-chat-button" style="padding:6px 10px;font-size:11px;line-height:1;border-radius:16px;display:inline-flex;align-items:center;gap:6px;writing-mode:horizontal-tb!important;transform:none!important;height:auto!important;width:auto!important;cursor:pointer"><i class="fa fa-comments"></i> Help</div>
<div id="aiPanel" class="ai-chat-panel" style="width:230px;height:260px;max-height:45vh;font-size:11px">
    <div class="ai-chat-header">Assistant (Admin)</div>
    <div id="aiBody" class="ai-chat-body"></div>
    <div class="ai-chat-input">
        <input id="aiInput" type="text" placeholder="Ask about settings, vouchers, reports..." />
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
            sendBtn.disabled=true;

            $.ajax({
                url: '{$app_url}/?_route=ai/chat',
                method:'POST',
                dataType:'json',
                data:{message:q, token: (window.AI_TOKEN||'')},
                success:function(res){
                    removeTyping(typingEl);
                    sendBtn.disabled=false;
                    var a=res&&res.result&&res.result.answer?res.result.answer:(res.message||'');
                    addMsg('ai-bot', a||'Sorry, I could not process your request. Please try again.');
                },
                error:function(xhr){
                    removeTyping(typingEl);
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

        // Welcome message for admin
        setTimeout(function(){
            if(bodyEl.children.length===0){
                addMsg('ai-bot', 'Hello Admin! I\'m your AI assistant for PHPNuxBill. I can help you with system configuration, customer management, reports, settings, troubleshooting, and more. What would you like to know?');
            }
        }, 1000);
    })();
</script>
{/literal}
{/if}
{literal}
    <script>
        var listAttApi;
        var posAttApi = 0;
        $(document).ready(function() {
            $('.select2').select2({theme: "bootstrap"});
            $('.select2tag').select2({theme: "bootstrap", tags: true});
            var listAtts = document.querySelectorAll(`button[type="submit"]`);
            listAtts.forEach(function(el) {
                if (el.addEventListener) { // all browsers except IE before version 9
                    el.addEventListener("click", function() {
                        var txt = $(this).html();
                        $(this).html(
                            `<span class="loading"></span>`
                        );
                        setTimeout(() => {
                            $(this).prop("disabled", true);
                        }, 100);
                        setTimeout(() => {
                            $(this).html(txt);
                            $(this).prop("disabled", false);
                        }, 5000);
                    }, false);
                } else {
                    if (el.attachEvent) { // IE before version 9
                        el.attachEvent("click", function() {
                            var txt = $(this).html();
                            $(this).html(
                                `<span class="loading"></span>`
                            );
                            setTimeout(() => {
                                $(this).prop("disabled", true);
                            }, 100);
                            setTimeout(() => {
                                $(this).html(txt);
                                $(this).prop("disabled", false);
                            }, 5000);
                        });
                    }
                }

            });
            setTimeout(() => {
                listAttApi = document.querySelectorAll(`[api-get-text]`);
                apiGetText();
            }, 500);
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

        function apiGetText(){
            var el = listAttApi[posAttApi];
            if(el != undefined){
                $.get(el.getAttribute('api-get-text'), function(data) {
                    el.innerHTML = data;
                    posAttApi++;
                    if(posAttApi < listAttApi.length){
                        apiGetText();
                    }
                });
            }
        }

        function setKolaps() {
            var kolaps = getCookie('kolaps');
            if (kolaps) {
                setCookie('kolaps', false, 30);
            } else {
                setCookie('kolaps', true, 30);
            }
            return true;
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

        $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        })
        $("[data-toggle=popover]").popover();
    </script>
{/literal}

</body>

</html>