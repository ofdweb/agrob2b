$(document).ready(function() {
            CertificateObj.prototype.DateTimePutTogether = function(certDate)
            {
                return this.check(certDate.getUTCDate())+"."+this.check(certDate.getMonth()+1)+"."+certDate.getFullYear() + " " +
                             this.check(certDate.getUTCHours()) + ":" + this.check(certDate.getUTCMinutes()) + ":" + this.check(certDate.getUTCSeconds());
            }
            
            CertificateObj.prototype.GetCertString = function()
            {
                return this.extract(this.cert.SubjectName,'CN=') + "; Выдан: " + this.GetCertFromDate();
            }
            
            CertificateObj.prototype.GetCertFromDate = function()
            {
                return this.DateTimePutTogether(this.certFromDate);
            }
            
            CertificateObj.prototype.GetCertTillDate = function()
            {
                return this.DateTimePutTogether(this.certTillDate);
            }
            
            CertificateObj.prototype.GetPubKeyAlgorithm = function()
            {
                return this.cert.PublicKey().Algorithm.FriendlyName;
            }
            
            CertificateObj.prototype.GetCertName = function()
            {
                return this.extract(this.cert.SubjectName, 'CN=');
            }
            
            CertificateObj.prototype.GetIssuer = function()
            {
                return this.extract(this.cert.IssuerName, 'CN=');
            }
            
            CertificateObj.prototype.check = function(digit)
            {
                return (digit<10) ? "0"+digit : digit;
            }
            
            CertificateObj.prototype.extract = function(from, what)
            {
                certName = "";
            
                var begin = from.indexOf(what);
            
                if(begin>=0)
                {
                    var end = from.indexOf(', ', begin);
            
                    if(end<0)
                    {
                        var end = from.indexOf(' ', begin);
                        certName = (end<0) ? from.substr(begin) : from.substr(begin, end - begin);
                    }
                    else
                    {
                        certName = from.substr(begin, end - begin);
                    }
                }
            
                return certName;
            }
            
            function CertificateObj(certObj)
            {
                this.cert = certObj;
                this.certFromDate = new Date(this.cert.ValidFromDate);
                this.certTillDate = new Date(this.cert.ValidToDate);
            }
            
            function SignCadesBES(certListBoxId) {
                var certificate = GetCertificate(certListBoxId);
                var dataToSign = document.getElementById("DataToSignTxtBox").value;
                var x = document.getElementsByName("SignatureTitle");
                try
                {
                    FillCertInfo(certificate);
                    var signature = MakeCadesBesSign(dataToSign, certificate);
                    document.getElementById("SignatureTxtBox").innerHTML = signature;
                    x[0].innerHTML = "Подпись сформирована успешно:";
                }
                catch(err)
                {
                    x[0].innerHTML = "Возникла ошибка:";
                    document.getElementById("SignatureTxtBox").innerHTML = err;
                }
            }        
                    
                    
            function getXmlHttp(){
                var xmlhttp;
                try {
                    xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
                } catch (e) {
                    try {
                        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                    } catch (E) {
                        xmlhttp = false;
                    }
                }
                if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
                    xmlhttp = new XMLHttpRequest();
                }
                return xmlhttp;
            }
            
            function ObjCreator(name) {
                switch (navigator.appName) {
                    case 'Microsoft Internet Explorer':
                        return new ActiveXObject(name);
                    default:
                        var userAgent = navigator.userAgent;
                        if (userAgent.match(/Trident\/./i)) { // IE10, 11
                            return new ActiveXObject(name);
                        }
                        if (userAgent.match(/ipod/i) || userAgent.match(/ipad/i) || userAgent.match(/iphone/i)) {
                            return call_ru_cryptopro_npcades_10_native_bridge("CreateObject", [name]);
                        }
                        var cadesobject = document.getElementById('cadesplugin');
                        return cadesobject.CreateObject(name);
                }
            }
            
            function GetCertificate(certListBoxId) {
                var e = document.getElementById(certListBoxId);
                var selectedCertID = e.selectedIndex;
                if (selectedCertID == -1) {
                    alert("Select certificate");
                    return;
                }
            
                var thumbprint = e.options[selectedCertID].value.split(" ").reverse().join("").replace(/\s/g, "").toUpperCase();
                try {
                    var oStore = ObjCreator("CAPICOM.store");
                    oStore.Open();
                } catch (err) {
                    alert('Failed to create CAPICOM.store: ' + err.number);
                    return;
                }
            
                var CAPICOM_CERTIFICATE_FIND_SHA1_HASH = 0;
                var oCerts = oStore.Certificates.Find(CAPICOM_CERTIFICATE_FIND_SHA1_HASH, thumbprint);
            
                if (oCerts.Count == 0) {
                    alert("Certificate not found");
                    return;
                }
                var oCert = oCerts.Item(1);
                return oCert;
            }
            
            function MakeCadesBesSign(dataToSign, certObject) {
                var errormes = "";
            
                try {
                    var oSigner = ObjCreator("CAdESCOM.CPSigner");
                } catch (err) {
                    errormes = "Failed to create CAdESCOM.CPSigner: " + err.number;
                    alert(errormes);
                    throw errormes;
                }
            
                if (oSigner) {
                    oSigner.Certificate = certObject;
                }
                else {
                    errormes = "Failed to create CAdESCOM.CPSigner";
                    alert(errormes);
                    throw errormes;
                }
            
                var oSignedData = ObjCreator("CAdESCOM.CadesSignedData");
                var CADES_BES = 1;
                var Signature;
            
                if (dataToSign) {
                    // Данные на подпись ввели
                    oSignedData.Content = dataToSign;
                    oSigner.Options = 1; //CAPICOM_CERTIFICATE_INCLUDE_WHOLE_CHAIN
                    try {
                        Signature = oSignedData.SignCades(oSigner, CADES_BES);
                    }
                    catch (err) {
                        errormes = "Не удалось создать подпись из-за ошибки: " + GetErrorMessage(err);
                        alert(errormes);
                        throw errormes;
                    }
                }
                return Signature;
            }
            
            function GetFirstCert() {
                var oStore = ObjCreator("CAPICOM.store");
                if (!oStore) {
                    alert("store failed");
                    return;
                }
            
                try {
                    oStore.Open();
                }
                catch (e) {
                    alert("Ошибка при открытии хранилища: " + GetErrorMessage(e));
                    return;
                }
            
                var dateObj = new Date();
                var certCnt;
            
                try {
                    certCnt = oStore.Certificates.Count;
                }
                catch (ex) {
                    if("Cannot find object or property. (0x80092004)" == GetErrorMessage(ex))
                    {
                        var errormes = document.getElementById("boxdiv").style.display = '';
                        return;
                    }
                }
            
                if(certCnt) {
                    try {
                        for (var i = 1; i <= certCnt; i++) {
                            var cert = oStore.Certificates.Item(i);
                            if(dateObj<cert.ValidToDate && cert.HasPrivateKey() && cert.IsValid()){
                                return cert;
                            }
                        }
                    }
                    catch (ex) {
                        alert("Ошибка при перечислении сертификатов: " + GetErrorMessage(ex));
                        return;
                    }
                }
            }
            
            function FillCertInfo(certificate)
            {
                var certObj = new CertificateObj(certificate);
                document.getElementById("cert_info").style.display = '';
                document.getElementById("subject").innerHTML = "Владелец: <b>" + certObj.GetCertName() + "<b>";
                document.getElementById("issuer").innerHTML = "Издатель: <b>" + certObj.GetIssuer() + "<b>";
                document.getElementById("from").innerHTML = "Выдан: <b>" + certObj.GetCertFromDate() + "<b>";
                document.getElementById("till").innerHTML = "Действителен до: <b>" + certObj.GetCertTillDate() + "<b>";
                document.getElementById("algorithm").innerHTML = "Алгоритм ключа: <b>" + certObj.GetPubKeyAlgorithm() + "<b>";
            }
        
            $('.start').on('click',function(){
                console.log('ddd');
                //var isPlugInExists = document.getElementById('isPlugInEnabled').getAttribute("value");
                var oCert;
                var isPlugInExists=1;
                if (isPlugInExists == "1") {
                    oCert = GetFirstCert();
                    var txtDataToSign = "Hello World";
                    //var x = document.getElementsByName("SignatureTitle");
                    try
                    {
                        var sSignedData = MakeCadesBesSign(txtDataToSign, oCert);
                        document.getElementById("deal-text").innerHTML = txtDataToSign;
                        document.getElementById("signature").innerHTML = sSignedData;
                        FillCertInfo(oCert);
                        x[0].innerHTML = "Подпись сформирована успешно:";
                    }
                    catch(err)
                    {
                        x[0].innerHTML = "Возникла ошибка:";
                        document.getElementById("signature").innerHTML = err;
                    }
                }
                                                
            });
    });