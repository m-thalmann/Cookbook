import { Injectable, OnDestroy } from '@angular/core';
import { TranslocoService } from '@ngneat/transloco';
import { Subscription, first, lastValueFrom, skip } from 'rxjs';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { ApiService } from '../api/api.service';
import { AuthService } from '../auth/auth.service';
import { SnackbarService } from '../services/snackbar.service';
import { StorageService } from '../services/storage.service';

const LANGUAGE_KEY = 'LANGUAGE';

const Logger = new LoggerClass('Settings');

@Injectable({
  providedIn: 'root',
})
export class LanguageService implements OnDestroy {
  private subSink = new Subscription();

  constructor(
    private auth: AuthService,
    private transloco: TranslocoService,
    private api: ApiService,
    private storage: StorageService,
    private snackbar: SnackbarService
  ) {
    this.subSink.add(
      this.auth.user$.subscribe((user) => {
        if (user) {
          this.transloco.setActiveLang(user.language_code || 'en');
        }
      })
    );

    this.subSink.add(
      // the first emission is skipped because it's the default language. See https://ngneat.github.io/transloco/docs/language-api#langchanges
      this.transloco.langChanges$.pipe(skip(1)).subscribe((language) => {
        this.storage.set(LANGUAGE_KEY, language);
      })
    );
  }

  init() {
    const language = this.storage.get<string>(LANGUAGE_KEY);

    if (language) {
      this.transloco.setActiveLang(language);
    }
  }

  async selectLanguage(language: string) {
    const user = await lastValueFrom(this.auth.user$.pipe(first()));

    if (!user) {
      this.transloco.setActiveLang(language);
      return;
    }

    try {
      const updateResponse = await lastValueFrom(this.api.users.update(user.id, { language_code: language }));

      this.auth.updateUser(updateResponse.body!.data);
    } catch (e) {
      this.snackbar.warn({ message: this.transloco.translate('messages.errors.savingLanguage'), duration: null });
      Logger.error('Error saving language to user:', e);
    }
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}
