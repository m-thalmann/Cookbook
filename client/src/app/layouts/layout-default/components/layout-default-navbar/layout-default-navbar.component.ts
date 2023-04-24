import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatToolbarModule } from '@angular/material/toolbar';
import { RouterModule } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { AuthService } from 'src/app/core/auth/auth.service';

@Component({
  selector: 'app-layout-default-navbar',
  templateUrl: './layout-default-navbar.component.html',
  styleUrls: ['./layout-default-navbar.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterModule, TranslocoModule, MatToolbarModule, MatIconModule, MatButtonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LayoutDefaultNavbarComponent {
  constructor(public auth: AuthService) {}
}
