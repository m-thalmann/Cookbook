import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatBottomSheet } from '@angular/material/bottom-sheet';
import { AuthService } from 'src/app/core/auth/auth.service';
import { AccountMenuBottomSheetComponent } from './components/account-menu-bottom-sheet/account-menu-bottom-sheet.component';

@Component({
  selector: 'app-layout-default',
  templateUrl: './layout-default.component.html',
  styleUrls: ['./layout-default.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LayoutDefaultComponent {
  constructor(public auth: AuthService, private bottomSheet: MatBottomSheet) {}

  openAccountMenu() {
    this.bottomSheet.open(AccountMenuBottomSheetComponent);
  }
}
