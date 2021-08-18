import { Component, ElementRef, EventEmitter, OnDestroy, OnInit, Output, ViewChild } from '@angular/core';
import { ConfigService } from 'src/app/core/config/config.service';

const HCAPTCHA_API = 'https://js.hcaptcha.com/1/api.js?render=explicit&onload=_hcaptchaOnLoad&recaptchacompat=off';

@Component({
  selector: 'cb-hcaptcha',
  template: `<div #hcaptcha></div>`,
  styleUrls: [],
})
export class HcaptchaComponent implements OnInit, OnDestroy {
  @ViewChild('hcaptcha', { static: true }) hcaptchaContainer!: ElementRef;

  @Output() verified = new EventEmitter<string>();

  constructor(private config: ConfigService) {}

  ngOnInit() {
    if (!this.hcaptcha) {
      (window as any)._hcaptchaOnLoad = () => {
        this.render();
      };

      const body = <HTMLDivElement>document.body;
      const script = document.createElement('script');
      script.innerHTML = '';
      script.src = HCAPTCHA_API;
      script.async = true;
      script.defer = true;
      body.appendChild(script);
    } else {
      this.render();
    }
  }

  ngOnDestroy() {
    delete (window as any)._hcaptchaOnLoad;
  }

  get hcaptcha() {
    return (window as any).hcaptcha;
  }

  render() {
    this.hcaptcha.render(this.hcaptchaContainer.nativeElement, {
      sitekey: this.config.get('hcaptcha.site_key'),
      callback: (res: string) => {
        this.verified.emit(res);
      },
    });
  }
}
