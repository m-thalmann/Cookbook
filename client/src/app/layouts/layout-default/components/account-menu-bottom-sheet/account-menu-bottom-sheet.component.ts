import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatBottomSheetRef } from '@angular/material/bottom-sheet';
import { AuthService } from 'src/app/core/auth/auth.service';

@Component({
  selector: 'app-account-menu-bottom-sheet',
  templateUrl: './account-menu-bottom-sheet.component.html',
  styleUrls: ['./account-menu-bottom-sheet.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AccountMenuBottomSheetComponent {
  constructor(private bottomSheetRef: MatBottomSheetRef<AccountMenuBottomSheetComponent>, public auth: AuthService) {}

  close() {
    this.bottomSheetRef.dismiss();
  }

  doLogout() {
    this.auth.logout();

    // TODO: show success notification

    this.close();
  }
}

