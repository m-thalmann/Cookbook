import { Component, OnInit } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from 'src/app/core/api/api.service';
import { RecipeFull } from 'src/app/core/api/ApiInterfaces';
import { UserService } from 'src/app/core/auth/user.service';

@Component({
  selector: 'cb-page-edit-recipe',
  templateUrl: './page-edit-recipe.component.html',
  styleUrls: ['./page-edit-recipe.component.scss'],
})
export class PageEditRecipeComponent implements OnInit {
  editRecipe: RecipeFull | null = null;

  loading = false;
  error = false;

  constructor(
    private route: ActivatedRoute,
    private api: ApiService,
    private snackBar: MatSnackBar,
    private router: Router,
    private user: UserService
  ) {}

  ngOnInit() {
    this.route.params.subscribe((params) => {
      if (!this.loading && (!this.editRecipe || this.editRecipe.id !== params.id)) {
        this.load(params.id);
      }
    });
  }

  async load(id: number) {
    this.loading = true;
    this.error = false;

    let res = await this.api.getRecipeById(id);

    if (res.isOK()) {
      if (res.value && (this.user.user?.isAdmin || res.value.user.id === this.user.user?.id)) {
        this.editRecipe = res.value;
      } else {
        this.snackBar.open('You do not own this recipe', 'OK', {
          panelClass: 'action-warn',
        });

        await this.router.navigateByUrl('/home');
      }
    } else if (res.isNotFound()) {
      this.snackBar.open('Recipe was not found', 'OK', {
        panelClass: 'action-warn',
        duration: 5000,
      });

      await this.router.navigateByUrl('/home');
    } else {
      this.error = true;

      console.error('Error loading recipe:', res.error);
    }

    this.loading = false;
  }
}
