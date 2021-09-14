import { Injectable, OnDestroy } from '@angular/core';
import { ApiService } from '../api/api.service';
import { UserService } from '../auth/user.service';
import { Logger, LoggerColor, SubSink } from '../functions';
import { TranslationService } from './translation.service';

@Injectable({
  providedIn: 'root',
})
export class UserTranslationService implements OnDestroy {
  private subSink = new SubSink();

  constructor(private translation: TranslationService, private user: UserService, private api: ApiService) {}

  init() {
    this.subSink.push(this.translation.languageSaved.subscribe(this.languageSaved.bind(this)));
  }

  private async languageSaved(language: string | null) {
    if (language && this.user.isLoggedin && this.user.user?.languageCode !== language) {
      let res = await this.api.updateUser({ languageCode: language });

      if (res.isOK()) {
        // TODO: refresh user -> remove following loc

        await this.api.checkAuthentication();
      } else {
        Logger.error('UserTranslationService', LoggerColor.orange, 'Error saving language to user:', res.error);
      }
    }
  }

  ngOnDestroy() {
    this.subSink.clear();
  }
}
